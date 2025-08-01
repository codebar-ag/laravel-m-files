<?php

declare(strict_types=1);

use CodebarAg\MFiles\Connectors\MFilesConnector;
use CodebarAg\MFiles\DTO\Authentication\AuthenticationToken;
use CodebarAg\MFiles\DTO\Config\ConfigWithCredentials;
use CodebarAg\MFiles\Fixtures\LogInToVaultFixture;
use CodebarAg\MFiles\Fixtures\LogOutFromVaultFixture;
use CodebarAg\MFiles\Helpers\CacheKeyManager;
use CodebarAg\MFiles\Requests\LogInToVaultRequest;
use CodebarAg\MFiles\Requests\LogOutFromVaultRequest;
use Saloon\Laravel\Facades\Saloon;

test('can logout from vault and clear authentication token from cache', function () {

    Saloon::fake([
        LogInToVaultRequest::class => new LogInToVaultFixture,
        LogOutFromVaultRequest::class => new LogOutFromVaultFixture,
    ]);

    $config = new ConfigWithCredentials(
        url: config('m-files.auth.url'),
        vaultGuid: config('m-files.vault_guid'),
        username: config('m-files.auth.username'),
        password: config('m-files.auth.password'),
    );

    $cacheManager = new CacheKeyManager($config);

    $cacheManager->removeAuthToken();
    expect($cacheManager->hasAuthToken())->toBeFalse();

    AuthenticationToken::getOrCreate($config);

    expect($cacheManager->hasAuthToken())->toBeTrue();

    $connector = new MFilesConnector($config);
    $request = new LogOutFromVaultRequest($config);
    $response = $connector->send($request);
    $response->dto();

    expect($cacheManager->hasAuthToken())->toBeFalse();
})->group('authentication', 'log-out-from-vault');
