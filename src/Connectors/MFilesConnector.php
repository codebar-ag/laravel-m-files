<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Connectors;

use CodebarAg\MFiles\DTO\ConfigWithCredentials;
use CodebarAg\MFiles\Helpers\CacheKeyManager;
use CodebarAg\MFiles\Requests\LogInToVaultRequest;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;

class MFilesConnector extends Connector
{
    use AcceptsJson;

    public function __construct(
        public ConfigWithCredentials $configuration,
        protected ?CacheKeyManager $cacheKeyManager = null,
    ) {}

    public function resolveBaseUrl(): string
    {
        return rtrim($this->configuration->url, '/').'/REST';
    }

    public function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-Authentication' => $this->getToken(),
        ];
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
