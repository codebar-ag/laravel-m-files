<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Requests;

use CodebarAg\MFiles\Responses\LogInToVaultResponse;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Http\SoloRequest;
use Saloon\Traits\Body\HasJsonBody;
use Saloon\Traits\Plugins\AcceptsJson;

class LogInToVaultRequest extends SoloRequest implements HasBody
{
    use AcceptsJson;
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        public string $url,
        public string $vaultGuid,
        public string $username,
        public string $password,
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
