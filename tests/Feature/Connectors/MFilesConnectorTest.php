<?php

declare(strict_types=1);

use CodebarAg\MFiles\Connectors\MFilesConnector;
use CodebarAg\MFiles\DTO\AuthenticationToken;
use CodebarAg\MFiles\DTO\ConfigWithCredentials;
use CodebarAg\MFiles\Helpers\CacheKeyManager;
use CodebarAg\MFiles\Requests\LogInToVaultRequest;
use CodebarAg\MFiles\Requests\LogOutFromVaultRequest;
use Illuminate\Support\Facades\Cache;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;

test('can create connector with default config', function () {

    Saloon::fake([
        LogInToVaultRequest::class => MockResponse::fixture('m-files-connector-login-to-vault'),
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
        LogInToVaultRequest::class => MockResponse::fixture('m-files-connector-login-to-vault'),
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
        LogInToVaultRequest::class => MockResponse::fixture('login-to-vault'),
        LogOutFromVaultRequest::class => MockResponse::fixture('logout-from-vault'),
    ]);

    $cacheManager = new CacheKeyManager($config);
    $cacheKey = $cacheManager->getAuthKey();

    // First, create an authentication token
    AuthenticationToken::getOrCreate($config);

    expect(Cache::store($config->cacheDriver)->has($cacheKey))->toBeTrue();

    $connector = new MFilesConnector($config);
    $logout = $connector->send(new LogOutFromVaultRequest($config))->dto();

    expect($logout)->toBeTrue();
    expect(Cache::store($config->cacheDriver)->has($cacheKey))->toBeFalse();
});
