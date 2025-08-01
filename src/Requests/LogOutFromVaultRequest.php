<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Requests;

use CodebarAg\MFiles\DTO\ConfigWithCredentials;
use CodebarAg\MFiles\Helpers\CacheKeyManager;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class LogOutFromVaultRequest extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        protected ConfigWithCredentials $configuration
    ) {}

    public function resolveEndpoint(): string
    {
        return '/session';
    }

    public function createDtoFromResponse(Response $response): bool
    {
        $cacheManager = new CacheKeyManager($this->configuration);
        $cacheManager->removeAuthToken();

        return $response->successful();
    }
}
