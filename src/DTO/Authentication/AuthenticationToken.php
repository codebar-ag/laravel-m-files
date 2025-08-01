<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\DTO\Authentication;

use CodebarAg\MFiles\DTO\Config\ConfigWithCredentials;
use CodebarAg\MFiles\Helpers\CacheKeyManager;
use CodebarAg\MFiles\Requests\LogInToVaultRequest;
use Illuminate\Support\Str;

class AuthenticationToken
{
    public function __construct(
        public string $value,
        public ?string $sessionId = null,
    ) {}

    public function toArray(): array
    {
        return [
            'Value' => $this->value,
            'SessionID' => $this->sessionId,
        ];
    }

    /**
     * Get or create an authentication token with caching
     */
    public static function getOrCreate(
        ConfigWithCredentials $config
    ): AuthenticationToken {

        $cacheManager = new CacheKeyManager($config);

        return $cacheManager->rememberAuthToken(3600, function () use ($config) {
            return new LogInToVaultRequest(
                url: $config->url,
                vaultGuid: $config->vaultGuid,
                username: $config->username,
                password: $config->password,
                sessionId: Str::uuid()->toString()
            )->send()->dto();
        });
    }
}
