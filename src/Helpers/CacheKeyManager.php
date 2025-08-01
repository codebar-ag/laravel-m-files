<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Helpers;

use CodebarAg\MFiles\DTO\Config\ConfigWithCredentials;
use Illuminate\Support\Facades\Cache;

class CacheKeyManager
{
    private const CACHE_PREFIX = 'm-files-auth';

    public function __construct(
        private ConfigWithCredentials $config
    ) {}

    /**
     * Generate cache key for authentication token
     */
    public function getAuthKey(): string
    {
        $uniqueString = $this->config->url.'|'.$this->config->vaultGuid.'|'.$this->config->username.'|'.$this->config->password.'|';

        return self::CACHE_PREFIX.':'.hash('sha256', $uniqueString);
    }

    /**
     * Get authentication token from cache
     */
    public function getAuthToken(): mixed
    {

        return Cache::store($this->config->cacheDriver)->get($this->getAuthKey());
    }

    /**
     * Set authentication token in cache
     */
    public function setAuthToken(mixed $token, int $ttl = 3600): bool
    {
        return Cache::store($this->config->cacheDriver)->put($this->getAuthKey(), $token, $ttl);
    }

    /**
     * Remove authentication token from cache
     */
    public function removeAuthToken(): bool
    {
        return Cache::store($this->config->cacheDriver)->forget($this->getAuthKey());
    }

    /**
     * Check if authentication token exists in cache
     */
    public function hasAuthToken(): bool
    {
        return Cache::store($this->config->cacheDriver)->has($this->getAuthKey());
    }

    /**
     * Remember authentication token with callback
     */
    public function rememberAuthToken(int $ttl, callable $callback): mixed
    {
        $key = $this->getAuthKey();

        return Cache::store($this->config->cacheDriver)->remember($key, $ttl, $callback);
    }
}
