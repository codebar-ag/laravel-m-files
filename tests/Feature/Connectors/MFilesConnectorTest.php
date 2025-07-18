<?php

declare(strict_types=1);

use CodebarAg\MFiles\DTO\Authentication\AuthenticationToken;
use CodebarAg\MFiles\DTO\Config\ConfigWithCredentials;
use CodebarAg\MFiles\Fixtures\AuthenticationTokenFixture;
use CodebarAg\MFiles\Fixtures\LogoutSessionFixture;
use CodebarAg\MFiles\Requests\Authentication\GetAuthenticationToken;
use CodebarAg\MFiles\Requests\Authentication\LogoutSession;
use Illuminate\Support\Facades\Cache;
use Saloon\Laravel\Facades\Saloon;

test('can create connector with default config', function () {
    Saloon::fake([
        GetAuthenticationToken::class => new AuthenticationTokenFixture,
    ]);

    $config = new ConfigWithCredentials;

    expect($config)->toBeInstanceOf(ConfigWithCredentials::class);
});

test('can create connector with default config then stores and retrieves from cache', function () {
    Saloon::fake([
        GetAuthenticationToken::class => new AuthenticationTokenFixture,
    ]);

    $config = new ConfigWithCredentials;

    $config2 = new ConfigWithCredentials;

    expect($config)->toBeInstanceOf(ConfigWithCredentials::class);
    expect($config->toArray())->toBe($config2->toArray());
});

test('can logout session', function () {
    $config = new ConfigWithCredentials;

    Saloon::fake([
        GetAuthenticationToken::class => new AuthenticationTokenFixture,
        LogoutSession::class => new LogoutSessionFixture,
    ]);

    $cacheKey = AuthenticationToken::generateCacheKey(
        url: $config->url,
        username: $config->username,
        password: $config->password,
        vaultGuid: $config->vaultGuid,
    );

    expect(Cache::store($config->cacheDriver)->has($cacheKey))->toBeTrue();

    $logout = new LogoutSession($config)->send()->dto();

    expect($logout)->toBeTrue();
    expect(Cache::store($config->cacheDriver)->has($cacheKey))->toBeFalse();
});
