<?php

declare(strict_types=1);

use CodebarAg\MFiles\DTO\ConfigWithCredentials;
use CodebarAg\MFiles\Helpers\CacheKeyManager;

uses()->group('helpers');

beforeEach(function () {
    $this->config = new ConfigWithCredentials(
        url: 'https://example.com',
        username: 'username',
        password: 'password',
        vaultGuid: 'vault-guid-123',
        cacheDriver: 'array'
    );

    $this->cacheManager = new CacheKeyManager($this->config);
});

it('generates auth key with correct format', function () {
    $key = $this->cacheManager->getAuthKey();

    expect($key)
        ->toStartWith('m-files-auth:')
        ->and($key)
        ->toEndWith(hash('sha256', 'https://example.com|vault-guid-123|username|password|'));
});

it('handles auth token operations correctly', function () {
    $testToken = ['token' => 'test-token-value'];

    expect($this->cacheManager->setAuthToken($testToken, 60))->toBeTrue();

    expect($this->cacheManager->hasAuthToken())->toBeTrue();

    expect($this->cacheManager->getAuthToken())->toBe($testToken);

    expect($this->cacheManager->removeAuthToken())->toBeTrue();
    expect($this->cacheManager->hasAuthToken())->toBeFalse();
});

it('generates consistent keys', function () {
    $key1 = $this->cacheManager->getAuthKey();
    $key2 = $this->cacheManager->getAuthKey();

    expect($key1)->toBe($key2);
});

it('generates different keys for different configs', function () {
    $config1 = new ConfigWithCredentials(
        url: 'https://example.com',
        username: 'username1',
        password: 'password',
        vaultGuid: 'vault-guid-123',
        cacheDriver: 'array'
    );

    $config2 = new ConfigWithCredentials(
        url: 'https://example.com',
        username: 'username2',
        password: 'password',
        vaultGuid: 'vault-guid-123',
        cacheDriver: 'array'
    );

    $manager1 = new CacheKeyManager($config1);
    $manager2 = new CacheKeyManager($config2);

    $key1 = $manager1->getAuthKey();
    $key2 = $manager2->getAuthKey();

    expect($key1)->not->toBe($key2);
});
