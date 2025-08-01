<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\DTO;

use Illuminate\Support\Arr;

class ConfigWithCredentials
{
    public function __construct(
        public string $url,
        public string $vaultGuid,
        public string $username,
        public string $password,
        public ?string $sessionId = null,
        public ?string $cacheDriver = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            url: Arr::get($data, 'url'),
            vaultGuid: Arr::get($data, 'vaultGuid'),
            username: Arr::get($data, 'username'),
            password: Arr::get($data, 'password'),
            sessionId: Arr::get($data, 'sessionId'),
            cacheDriver: Arr::get($data, 'cacheDriver', config('m-files.cache_driver')),
        );
    }

    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'vaultGuid' => $this->vaultGuid,
            'username' => $this->username,
            'password' => $this->password,
            'sessionId' => $this->sessionId,
            'cacheDriver' => $this->cacheDriver,
        ];
    }
}
