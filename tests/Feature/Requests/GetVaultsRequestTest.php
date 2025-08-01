<?php

declare(strict_types=1);

use CodebarAg\MFiles\Connectors\MFilesConnector;
use CodebarAg\MFiles\DTO\ConfigWithCredentials;
use CodebarAg\MFiles\Requests\GetVaultsRequest;
use CodebarAg\MFiles\Requests\LogInToVaultRequest;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;

test('can logout from vault and clear authentication token from cache', function () {

    Saloon::fake([
        LogInToVaultRequest::class => MockResponse::fixture('get-vauls-login-to-vault'),
        GetVaultsRequest::class => MockResponse::fixture('get-vaults'),
    ]);

    $config = new ConfigWithCredentials(
        url: config('m-files.auth.url'),
        vaultGuid: config('m-files.vault_guid'),
        username: config('m-files.auth.username'),
        password: config('m-files.auth.password'),
    );

    $connector = new MFilesConnector($config);
    $request = new GetVaultsRequest($config);
    $response = $connector->send($request);
    $response->dto();

    expect($response->successful())->toBeTrue();
})->group('vaults', 'get-vaults');
