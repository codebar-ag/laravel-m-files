<?php

declare(strict_types=1);

use CodebarAg\MFiles\DTO\Authentication\AuthenticationToken;
use CodebarAg\MFiles\DTO\Config\ConfigWithCredentials;
use CodebarAg\MFiles\Fixtures\LogInToVaultFixture;
use CodebarAg\MFiles\Helpers\CacheKeyManager;
use CodebarAg\MFiles\Requests\LogInToVaultRequest;
use Saloon\Laravel\Facades\Saloon;

test('can login to vault and get authentication token with caching', function () {

    Saloon::fake([
        LogInToVaultRequest::class => new LogInToVaultFixture,
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

    $request = new LogInToVaultRequest(
        url: config('m-files.auth.url'),
        vaultGuid: config('m-files.vault_guid'),
        username: config('m-files.auth.username'),
        password: config('m-files.auth.password'),
    );

    $response = $request->send();
    $token = $response->dto();

    expect($token)->toBeInstanceOf(AuthenticationToken::class);
    expect($token->value)->toBeString();
    expect($token->value)->not->toBeEmpty();

    $cachedToken = AuthenticationToken::getOrCreate($config);
    expect($cachedToken)->toBeInstanceOf(AuthenticationToken::class);
    expect($cachedToken->value)->toBeString();
    expect($cachedToken->value)->not->toBeEmpty();

    expect($cacheManager->hasAuthToken())->toBeTrue();

    $retrievedToken = $cacheManager->getAuthToken();
    expect($retrievedToken)->toBeInstanceOf(AuthenticationToken::class);
    expect($retrievedToken->value)->toBe($cachedToken->value);
})->group('authentication', 'log-in-to-vault');
