<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\DTO;

use Illuminate\Support\Arr;
use InvalidArgumentException;

class ConfigWithCredentials
{
    public const DEFAULT_CONNECT_TIMEOUT = 10;

    public const DEFAULT_REQUEST_TIMEOUT = 60;

    public const DEFAULT_TRIES = 3;

    public const DEFAULT_RETRY_INTERVAL_MILLISECONDS = 500;

    /**
     * The transport settings are appended after the existing parameters so positional
     * construction keeps working. Guzzle defaults both timeouts to "wait forever",
     * which lets a single unresponsive vault pin a PHP worker indefinitely.
     */
    public function __construct(
        public string $url,
        public string $vaultGuid,
        public string $username,
        public string $password,
        public ?string $cacheDriver = null,
        public int $tokenTtlSeconds = 3600,
        public int $connectTimeout = self::DEFAULT_CONNECT_TIMEOUT,
        public int $requestTimeout = self::DEFAULT_REQUEST_TIMEOUT,
        public int $tries = self::DEFAULT_TRIES,
        public int $retryIntervalMilliseconds = self::DEFAULT_RETRY_INTERVAL_MILLISECONDS,
    ) {
        $this->tokenTtlSeconds = max(1, $this->tokenTtlSeconds);
        $this->connectTimeout = max(0, $this->connectTimeout);
        $this->requestTimeout = max(0, $this->requestTimeout);
        $this->tries = max(1, $this->tries);
        $this->retryIntervalMilliseconds = max(0, $this->retryIntervalMilliseconds);
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
            connectTimeout: self::optionalPositiveInt(
                $data,
                'connectTimeout',
                (int) config('m-files.http.connect_timeout', self::DEFAULT_CONNECT_TIMEOUT),
            ),
            requestTimeout: self::optionalPositiveInt(
                $data,
                'requestTimeout',
                (int) config('m-files.http.timeout', self::DEFAULT_REQUEST_TIMEOUT),
            ),
            tries: self::optionalPositiveInt(
                $data,
                'tries',
                (int) config('m-files.http.tries', self::DEFAULT_TRIES),
            ),
            retryIntervalMilliseconds: self::optionalPositiveInt(
                $data,
                'retryIntervalMilliseconds',
                (int) config('m-files.http.retry_interval', self::DEFAULT_RETRY_INTERVAL_MILLISECONDS),
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
            'connectTimeout' => $this->connectTimeout,
            'requestTimeout' => $this->requestTimeout,
            'tries' => $this->tries,
            'retryIntervalMilliseconds' => $this->retryIntervalMilliseconds,
        ];
    }

    /**
     * Keep the vault password out of dumps and stack traces.
     *
     * `dd()`, `var_dump()`, Ray and most exception renderers walk public properties,
     * so an unhandled error anywhere near this DTO used to print the credential.
     * toArray() intentionally still returns it so fromArray(toArray()) round-trips.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [...$this->toArray(), 'password' => '********'];
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
        $value = Arr::get($data, $key);

        if ($value === null) {
            return max(1, $default);
        }

        $int = match (true) {
            is_int($value) => $value,
            is_string($value) && ctype_digit($value) => (int) $value,
            default => throw new InvalidArgumentException("Config [{$key}] must be a positive integer."),
        };

        return max(1, $int);
    }
}
