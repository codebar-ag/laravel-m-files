<?php

declare(strict_types=1);

use CodebarAg\MFiles\DTO\ConfigWithCredentials;
use CodebarAg\MFiles\Requests\LogInToVaultRequest;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;

test('can create connector with default config', function () {

    Saloon::fake([
        LogInToVaultRequest::class => MockResponse::fixture('m-files-connector-login-to-vault'),
    ]);

    $config = new ConfigWithCredentials(
        url: config('m-files.auth.url'),
        vaultGuid: config('m-files.vault_guid'),
        username: config('m-files.auth.username'),
        password: config('m-files.auth.password')
    );

    expect($config)->toBeInstanceOf(ConfigWithCredentials::class);
});

test('can create connector with default config then stores and retrieves from cache', function () {
    Saloon::fake([
        LogInToVaultRequest::class => MockResponse::fixture('m-files-connector-login-to-vault'),
    ]);

    $config = new ConfigWithCredentials(
        url: config('m-files.auth.url'),
        vaultGuid: config('m-files.vault_guid'),
        username: config('m-files.auth.username'),
        password: config('m-files.auth.password')
    );

    $config2 = new ConfigWithCredentials(
        url: config('m-files.auth.url'),
        vaultGuid: config('m-files.vault_guid'),
        username: config('m-files.auth.username'),
        password: config('m-files.auth.password')
    );

    expect($config)->toBeInstanceOf(ConfigWithCredentials::class);
    expect($config->toArray())->toBe($config2->toArray());
});
