<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Connectors;

use CodebarAg\MFiles\DTO\ConfigWithCredentials;
use CodebarAg\MFiles\Helpers\CacheKeyManager;
use CodebarAg\MFiles\Requests\LogInToVaultRequest;
use Saloon\Enums\Method;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Connector;
use Saloon\Http\Request;
use Saloon\Traits\Plugins\AcceptsJson;
use Saloon\Traits\Plugins\HasTimeout;

class MFilesConnector extends Connector
{
    use AcceptsJson;
    use HasTimeout;

    /**
     * Statuses where M-Files (or something in front of it) rejected the request
     * without processing it, so replaying is safe for any HTTP method.
     */
    private const SAFE_TO_REPLAY_STATUSES = [408, 429, 503];

    /**
     * Server-side faults that may or may not have been processed. Only replayed for
     * idempotent methods — retrying a POST here could create a duplicate document.
     */
    private const AMBIGUOUS_SERVER_STATUSES = [500, 502, 504];

    private const IDEMPOTENT_METHODS = [Method::GET, Method::HEAD, Method::OPTIONS];

    public int $connectTimeout;

    public int $requestTimeout;

    public function __construct(
        public ConfigWithCredentials $configuration,
        protected ?CacheKeyManager $cacheKeyManager = null,
    ) {
        // Read by the HasTimeout plugin. Guzzle otherwise waits forever, so one
        // unresponsive vault could pin every PHP worker in the pool.
        $this->connectTimeout = $this->configuration->connectTimeout;
        $this->requestTimeout = $this->configuration->requestTimeout;

        $this->tries = $this->configuration->tries;
        $this->retryInterval = $this->configuration->retryIntervalMilliseconds;
        $this->useExponentialBackoff = true;

        // Saloon would otherwise throw its own RequestException once the attempts are
        // exhausted. Returning the last response instead keeps the failure flowing
        // through the response classes, so callers still catch MFilesErrorException.
        $this->throwOnMaxTries = false;
    }

    public function resolveBaseUrl(): string
    {
        return rtrim($this->configuration->url, '/').'/REST';
    }

    public function defaultHeaders(): array
    {
        // Deliberately no Content-Type here: connector headers are merged *after* the
        // body plugins boot, so a global Content-Type overwrites the one each request
        // sets for itself — including the multipart boundary on UploadFileRequest.
        // Requests using HasJsonBody set 'application/json' themselves.
        return [
            'Accept' => 'application/json',
            'X-Authentication' => $this->getToken(),
        ];
    }

    /**
     * Decide whether a failed attempt should be replayed.
     *
     * Deliberately conservative: a blanket retry would replay document-creating POSTs
     * that the vault may already have committed.
     */
    public function handleRetry(FatalRequestException|RequestException $exception, Request $request): bool
    {
        // Connection-level failure: the request never reached the application, so
        // replaying it cannot duplicate anything.
        if ($exception instanceof FatalRequestException) {
            return true;
        }

        $status = $exception->getResponse()->status();

        // The cached token outlives its server-side session whenever the vault
        // restarts or expires it early, and nothing invalidated our copy — so every
        // request 401'd until the TTL lapsed. Drop it and let the next attempt
        // re-authenticate. Genuinely bad credentials fail fast instead, because the
        // login request throws MFilesErrorException outside this retry loop.
        if ($status === 401) {
            $this->resolveCacheKeyManager()->removeAuthToken();

            return true;
        }

        if (in_array($status, self::SAFE_TO_REPLAY_STATUSES, strict: true)) {
            return true;
        }

        if (in_array($status, self::AMBIGUOUS_SERVER_STATUSES, strict: true)) {
            return in_array($request->getMethod(), self::IDEMPOTENT_METHODS, strict: true);
        }

        return false;
    }

    public function getToken(): ?string
    {
        return $this->resolveCacheKeyManager()->rememberAuthToken(
            $this->configuration->tokenTtlSeconds,
            function () {
                $request = new LogInToVaultRequest(
                    url: $this->configuration->url,
                    vaultGuid: $this->configuration->vaultGuid,
                    username: $this->configuration->username,
                    password: $this->configuration->password,
                    connectTimeout: $this->configuration->connectTimeout,
                    requestTimeout: $this->configuration->requestTimeout,
                );
                $response = $request->send();

                return $response->dto();
            }
        );
    }

    protected function resolveCacheKeyManager(): CacheKeyManager
    {
        return $this->cacheKeyManager ?? new CacheKeyManager($this->configuration);
    }
}
