<?php

declare(strict_types=1);

use CodebarAg\MFiles\DTO\Authentication\AuthenticationToken;
use CodebarAg\MFiles\DTO\Config\ConfigWithCredentials;
use CodebarAg\MFiles\Fixtures\LogInToVaultFixture;
use CodebarAg\MFiles\Fixtures\LogOutFromVaultFixture;
use CodebarAg\MFiles\Requests\LogInToVaultRequest;
use CodebarAg\MFiles\Requests\LogOutFromVaultRequest;
use Illuminate\Support\Facades\Cache;
use Saloon\Laravel\Facades\Saloon;

test('can create connector with default config', function () {

    Saloon::fake([
        LogInToVaultRequest::class => new LogInToVaultFixture,
    ]);

    $config = new ConfigWithCredentials(
        url: 'https://test.m-files.com',
        vaultGuid: 'test-vault-guid',
        username: 'test-user',
        password: 'test-password',
        cacheDriver: 'array'
    );

    expect($config)->toBeInstanceOf(ConfigWithCredentials::class);
});

test('can create connector with default config then stores and retrieves from cache', function () {
    Saloon::fake([
        LogInToVaultRequest::class => new LogInToVaultFixture,
    ]);

    $config = new ConfigWithCredentials(
        url: 'https://test.m-files.com',
        vaultGuid: 'test-vault-guid',
        username: 'test-user',
        password: 'test-password',
        cacheDriver: 'array'
    );

    $config2 = new ConfigWithCredentials(
        url: 'https://test.m-files.com',
        vaultGuid: 'test-vault-guid',
        username: 'test-user',
        password: 'test-password',
        cacheDriver: 'array'
    );

    expect($config)->toBeInstanceOf(ConfigWithCredentials::class);
    expect($config->toArray())->toBe($config2->toArray());
});

test('can logout session', function () {
    $config = new ConfigWithCredentials(
        url: 'https://test.m-files.com',
        vaultGuid: 'test-vault-guid',
        username: 'test-user',
        password: 'test-password',
        cacheDriver: 'array'
    );

    Saloon::fake([
        LogInToVaultRequest::class => new LogInToVaultFixture,
        LogOutFromVaultRequest::class => new LogOutFromVaultFixture,
    ]);

    $cacheKey = AuthenticationToken::generateCacheKey(
        url: $config->url,
        username: $config->username,
        password: $config->password,
        vaultGuid: $config->vaultGuid,
    );

    expect(Cache::store($config->cacheDriver)->has($cacheKey))->toBeTrue();

    $logout = new LogOutFromVaultRequest($config)->send()->dto();

    expect($logout)->toBeTrue();
    expect(Cache::store($config->cacheDriver)->has($cacheKey))->toBeFalse();
});
