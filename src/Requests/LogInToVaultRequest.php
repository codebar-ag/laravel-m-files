<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Requests;

use CodebarAg\MFiles\DTO\Authentication\AuthenticationToken;
use Illuminate\Support\Arr;
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
        public ?string $sessionId = null,
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
        $body = [
            'VaultGuid' => $this->vaultGuid,
            'Username' => $this->username,
            'Password' => $this->password,
            'Expiration' => now()->addDay()->toIso8601String(),
            'SessionID' => $this->sessionId,
        ];

        return $body;
    }

    public function createDtoFromResponse(Response $response): AuthenticationToken
    {
        $responseData = $response->json();

        if (! is_array($responseData)) {
            throw new \InvalidArgumentException('Invalid response format: expected array');
        }

        $value = Arr::get($responseData, 'Value');
        if (empty($value)) {
            throw new \InvalidArgumentException('Authentication token value not found in response');
        }

        return new AuthenticationToken(
            value: $value,
            sessionId: $this->sessionId,
        );
    }
}
