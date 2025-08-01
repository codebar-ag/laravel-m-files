<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Requests\Authentication;

use CodebarAg\MFiles\DTO\Authentication\AuthenticationToken;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Http\SoloRequest;
use Saloon\Traits\Body\HasJsonBody;
use Saloon\Traits\Plugins\AcceptsJson;

class GetAuthenticationToken extends SoloRequest implements HasBody
{
    use AcceptsJson;
    use HasJsonBody;

    protected Method $method = Method::POST;

    private string $sessionId;

    public function __construct(
        public string $url,
        public string $username,
        public string $password,
        public ?string $vaultGuid = null,
        public ?string $expiration = null,
    ) {
        $this->sessionId = Str::uuid()->toString();
    }

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
        $body = [
            'Username' => $this->username,
            'Password' => $this->password,
            'VaultGuid' => $this->vaultGuid,
            'SessionID' => $this->sessionId,
        ];

        if ($this->expiration) {
            $body['Expiration'] = $this->expiration;
        }

        return $body;
    }

    public function createDtoFromResponse(Response $response): AuthenticationToken
    {
        $responseData = $response->json();

        $data = [
            'Value' => Arr::get($responseData, 'Value', $responseData),
            'SessionID' => $this->sessionId,
            'Expiration' => $this->expiration,
        ];

        return new AuthenticationToken(
            value: Arr::get($data, 'Value', $responseData),
            expiration: $this->expiration ? \Carbon\CarbonImmutable::parse($this->expiration) : null,
            sessionId: $this->sessionId,
        );
    }
}
