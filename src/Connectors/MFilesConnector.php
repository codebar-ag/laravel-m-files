<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Connectors;

use CodebarAg\MFiles\DTO\Authentication\AuthenticationToken;
use CodebarAg\MFiles\DTO\Config\ConfigWithCredentials;
use Saloon\Http\Auth\HeaderAuthenticator;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;

class MFilesConnector extends Connector
{
    use AcceptsJson;

    public function __construct(
        public ConfigWithCredentials $configuration
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
        ];
    }

    protected function defaultAuth(): HeaderAuthenticator
    {
        $token = AuthenticationToken::getOrCreate($this->configuration);

        return new HeaderAuthenticator($token->value, 'X-Authentication');
    }
}
