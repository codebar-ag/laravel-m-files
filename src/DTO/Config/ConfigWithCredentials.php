<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\DTO\Config;

use CodebarAg\MFiles\DTO\Authentication\AuthenticationToken;
use Illuminate\Support\Arr;

class ConfigWithCredentials
{
    public function __construct(
        public ?string $url = null,
        public ?string $username = null,
        public ?string $password = null,
        public ?string $cacheDriver = null,
        public ?string $vaultGuid = null,
        public ?AuthenticationToken $authenticationToken = null
    ) {
        $this->url = $this->url ?? config('m-files.auth.url');
        $this->username = $this->username ?? config('m-files.auth.username');
        $this->password = $this->password ?? config('m-files.auth.password');
        $this->cacheDriver = $this->cacheDriver ?? config('m-files.cache_driver');
        $this->vaultGuid = $this->vaultGuid ?? config('m-files.vault_guid');

        $this->authenticationToken = $this->authenticationToken ?? AuthenticationToken::getOrCreate(
            url: $this->url,
            username: $this->username,
            password: $this->password,
            vaultGuid: $this->vaultGuid,
            cacheDriver: $this->cacheDriver,
        );
    }

    public static function fromArray(array $data): self
    {
        $authenticationToken = Arr::get($data, 'authenticationToken');

        return new self(
            url: Arr::get($data, 'url'),
            username: Arr::get($data, 'username'),
            password: Arr::get($data, 'password'),
            cacheDriver: Arr::get($data, 'cacheDriver'),
            vaultGuid: Arr::get($data, 'vaultGuid'),
            authenticationToken: $authenticationToken ? new AuthenticationToken(
                value: Arr::get($authenticationToken, 'Value', ''),
                expiration: Arr::get($authenticationToken, 'Expiration') ? \Carbon\CarbonImmutable::parse(Arr::get($authenticationToken, 'Expiration')) : null,
                sessionId: Arr::get($authenticationToken, 'SessionID')
            ) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'username' => $this->username,
            'password' => $this->password,
            'cacheDriver' => $this->cacheDriver,
            'vaultGuid' => $this->vaultGuid,
            'authenticationToken' => $this->authenticationToken?->toArray(),
        ];
    }
}
