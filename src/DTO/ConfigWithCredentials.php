<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\DTO;

use Illuminate\Support\Arr;
use InvalidArgumentException;

class ConfigWithCredentials
{
    public function __construct(
        public string $url,
        public string $vaultGuid,
        public string $username,
        public string $password,
        public ?string $cacheDriver = null,
        public int $tokenTtlSeconds = 3600,
    ) {
        if ($this->tokenTtlSeconds < 1) {
            throw new InvalidArgumentException('Config [tokenTtlSeconds] must be at least 1 second.');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            url: self::requireNonEmptyString($data, 'url'),
            vaultGuid: self::requireNonEmptyString($data, 'vaultGuid'),
            username: self::requireNonEmptyString($data, 'username'),
            password: self::requireNonEmptyString($data, 'password'),
            cacheDriver: self::optionalString($data, 'cacheDriver', config('m-files.cache_driver')),
            tokenTtlSeconds: self::optionalPositiveInt(
                $data,
                'tokenTtlSeconds',
                (int) config('m-files.auth.expiration', 3600),
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'vaultGuid' => $this->vaultGuid,
            'username' => $this->username,
            'password' => $this->password,
            'cacheDriver' => $this->cacheDriver,
            'tokenTtlSeconds' => $this->tokenTtlSeconds,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function requireNonEmptyString(array $data, string $key): string
    {
        $value = Arr::get($data, $key);

        if (! is_string($value) || $value === '') {
            throw new InvalidArgumentException("Config [{$key}] must be a non-empty string.");
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function optionalString(array $data, string $key, ?string $default): ?string
    {
        if (! Arr::has($data, $key)) {
            return $default;
        }

        $value = Arr::get($data, $key);

        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw new InvalidArgumentException("Config [{$key}] must be a string or null.");
        }

        if ($value === '') {
            return null;
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function optionalPositiveInt(array $data, string $key, int $default): int
    {
        if (! Arr::has($data, $key)) {
            return max(1, $default);
        }

        $value = Arr::get($data, $key);

        if ($value === null) {
            return max(1, $default);
        }

        if (is_int($value)) {
            $int = $value;
        } elseif (is_string($value) && ctype_digit($value)) {
            $int = (int) $value;
        } else {
            throw new InvalidArgumentException("Config [{$key}] must be a positive integer.");
        }

        if ($int < 1) {
            throw new InvalidArgumentException("Config [{$key}] must be at least 1 second.");
        }

        return $int;
    }
}
