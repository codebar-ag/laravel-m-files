<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Requests\Authentication;

use CodebarAg\MFiles\DTO\Authentication\AuthenticationToken;
use CodebarAg\MFiles\DTO\Config\ConfigWithCredentials;
use Illuminate\Support\Facades\Cache;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Http\SoloRequest;

class LogoutSession extends SoloRequest
{
    protected Method $method = Method::DELETE;

    public function __construct(
        protected ConfigWithCredentials $mFilesConfig
    ) {}

    public function resolveEndpoint(): string
    {
        return rtrim($this->mFilesConfig->url, '/').'/REST/session';
    }

    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-Authentication' => $this->mFilesConfig->authenticationToken->value,
        ];
    }

    public function createDtoFromResponse(Response $response): bool
    {
        if ($response->successful()) {
            // Remove the authentication token from cache after successful logout
            $cacheKey = AuthenticationToken::generateCacheKey(
                $this->mFilesConfig->url,
                $this->mFilesConfig->username,
                $this->mFilesConfig->password,
                $this->mFilesConfig->vaultGuid
            );

            Cache::store($this->mFilesConfig->cacheDriver)->forget($cacheKey);
        }

        return $response->successful();
    }
}
