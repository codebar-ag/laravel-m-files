<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\DTO\Authentication;

use Carbon\CarbonImmutable;
use CodebarAg\MFiles\Requests\Authentication\GetAuthenticationToken;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class AuthenticationToken
{
    public function __construct(
        public string $value,
        public ?CarbonImmutable $expiration = null,
        public ?string $sessionId = null
    ) {}

    public static function fromArray(array $data): self
    {
        $expiration = Arr::get($data, 'Expiration');

        return new self(
            value: Arr::get($data, 'Value', ''),
            expiration: $expiration ? CarbonImmutable::parse($expiration) : null,
            sessionId: Arr::get($data, 'SessionID')
        );
    }

    public function toArray(): array
    {
        return [
            'Value' => $this->value,
            'Expiration' => $this->expiration?->toISOString(),
            'SessionID' => $this->sessionId,
        ];
    }

    /**
     * Get or create an authentication token with caching
     */
    public static function getOrCreate(
        string $url,
        string $username,
        string $password,
        ?string $vaultGuid = null,
        ?string $cacheDriver = null,
    ): AuthenticationToken {
        $cacheKey = self::generateCacheKey($url, $username, $password, $vaultGuid);
        $cacheDriver = $cacheDriver ?? config('m-files.cache_driver');

        $cachedToken = Cache::store($cacheDriver)->get($cacheKey);

        if ($cachedToken && $cachedToken->isValid()) {
            return $cachedToken;
        }

        // Create new token
        $expirationInSeconds = (int) config('m-files.auth.expiration', 3600);
        $expiration = CarbonImmutable::now()->addSeconds($expirationInSeconds)->toISOString();

        $token = new GetAuthenticationToken(
            url: $url,
            username: $username,
            password: $password,
            vaultGuid: $vaultGuid,
            expiration: $expiration
        )->send()->dto();

        // Store in cache
        self::storeInCache($cacheKey, $token, $cacheDriver);

        return $token;
    }

    /**
     * Check if the token is still valid
     */
    public function isValid(): bool
    {
        // If no expiration is set, consider the token valid
        // (it will be managed by the cache TTL instead)
        if (! $this->expiration) {
            return true;
        }

        $currentTime = CarbonImmutable::now();

        // Consider token valid if it expires in more than 5 minutes
        return $this->expiration->gt($currentTime->addMinutes(5));
    }

    /**
     * Generate a unique cache key based on URL, username, password, and vault GUID
     */
    public static function generateCacheKey(string $url, string $username, string $password, ?string $vaultGuid = null): string
    {
        $uniqueString = $url.'|'.$username.'|'.$password.'|'.($vaultGuid ?? 'default');

        return 'm-files-auth-token:'.hash('sha256', $uniqueString);
    }

    /**
     * Store the authentication token in cache
     */
    private static function storeInCache(string $cacheKey, self $token, string $cacheDriver): void
    {
        // Use the same expiration time from config for cache TTL
        $ttl = config('m-files.auth.expiration', 3600);

        // Ensure TTL is positive and reasonable
        $ttl = max(300, min($ttl, 3600)); // Between 5 minutes and 1 hour

        Cache::store($cacheDriver)->put($cacheKey, $token, $ttl);
    }
}
