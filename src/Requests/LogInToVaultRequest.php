<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Requests;

use CodebarAg\MFiles\DTO\ConfigWithCredentials;
use CodebarAg\MFiles\Responses\LogInToVaultResponse;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Http\SoloRequest;
use Saloon\Traits\Body\HasJsonBody;
use Saloon\Traits\Plugins\AcceptsJson;
use Saloon\Traits\Plugins\HasTimeout;

class LogInToVaultRequest extends SoloRequest implements HasBody
{
    use AcceptsJson;
    use HasJsonBody;
    use HasTimeout;

    protected Method $method = Method::POST;

    /**
     * The timeouts are carried on the request rather than the connector because this
     * is a SoloRequest: it sends through Saloon's NullConnector, so MFilesConnector's
     * transport settings never applied to the login call. Without them an
     * unresponsive vault could hang the very first request indefinitely.
     */
    public function __construct(
        public string $url,
        public string $vaultGuid,
        public string $username,
        public string $password,
        public int $connectTimeout = ConfigWithCredentials::DEFAULT_CONNECT_TIMEOUT,
        public int $requestTimeout = ConfigWithCredentials::DEFAULT_REQUEST_TIMEOUT,
    ) {}

    public function resolveEndpoint(): string
    {
        return $this->url.'/REST/server/authenticationtokens';
    }

    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function defaultBody(): array
    {
        return [
            'Username' => $this->username,
            'Password' => $this->password,
            'VaultGuid' => $this->vaultGuid,
        ];
    }

    public function createDtoFromResponse(Response $response): ?string
    {
        return LogInToVaultResponse::createDtoFromResponse($response);
    }
}
