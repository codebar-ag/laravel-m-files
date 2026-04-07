<?php

declare(strict_types=1);

use CodebarAg\MFiles\Connectors\MFilesConnector;
use CodebarAg\MFiles\DTO\ConfigWithCredentials;
use CodebarAg\MFiles\Helpers\CacheKeyManager;
use CodebarAg\MFiles\Requests\LogInToVaultRequest;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;

test('getToken caches the authentication token and only sends login once', function () {
    Saloon::fake([
        LogInToVaultRequest::class => MockResponse::fixture('m-files-connector-login-to-vault'),
    ]);

    $config = new ConfigWithCredentials(
        url: 'https://mfiles.test',
        vaultGuid: 'vault-guid',
        username: 'user',
        password: 'secret',
        cacheDriver: 'array',
        tokenTtlSeconds: 3600,
    );

    $connector = new MFilesConnector(configuration: $config);

    $first = $connector->getToken();
    $second = $connector->getToken();

    expect($first)->toBeString();
    expect($first !== '')->toBeTrue();
    expect($second)->toBe($first);

    Saloon::assertSentCount(1);
})->group('connectors');

test('defaultHeaders includes X-Authentication from cached token', function () {
    Saloon::fake([
        LogInToVaultRequest::class => MockResponse::fixture('m-files-connector-login-to-vault'),
    ]);

    $config = new ConfigWithCredentials(
        url: 'https://mfiles.test',
        vaultGuid: 'vault-guid',
        username: 'user',
        password: 'secret',
        cacheDriver: 'array',
        tokenTtlSeconds: 3600,
    );

    $connector = new MFilesConnector(configuration: $config);
    $token = $connector->getToken();

    $headers = $connector->defaultHeaders();

    expect($headers['X-Authentication'])->toBe($token);
    Saloon::assertSentCount(1);
})->group('connectors');

test('it accepts an injected CacheKeyManager', function () {
    Saloon::fake([
        LogInToVaultRequest::class => MockResponse::fixture('m-files-connector-login-to-vault'),
    ]);

    $config = new ConfigWithCredentials(
        url: 'https://mfiles.test',
        vaultGuid: 'vault-guid',
        username: 'user',
        password: 'secret',
        cacheDriver: 'array',
        tokenTtlSeconds: 3600,
    );

    $cacheKeyManager = new CacheKeyManager($config);
    $connector = new MFilesConnector(configuration: $config, cacheKeyManager: $cacheKeyManager);

    $token = $connector->getToken();
    expect($token)->toBeString();
    expect($token !== '')->toBeTrue();
    Saloon::assertSentCount(1);
})->group('connectors');

test('two configs with identical credentials produce identical toArray payloads', function () {
    $config = new ConfigWithCredentials(
        url: 'https://mfiles.test',
        vaultGuid: 'vault-guid',
        username: 'user',
        password: 'secret',
        cacheDriver: 'array',
        tokenTtlSeconds: 3600,
    );

    $config2 = new ConfigWithCredentials(
        url: 'https://mfiles.test',
        vaultGuid: 'vault-guid',
        username: 'user',
        password: 'secret',
        cacheDriver: 'array',
        tokenTtlSeconds: 3600,
    );

    expect($config->toArray())->toBe($config2->toArray());
})->group('connectors');
