<?php

declare(strict_types=1);

use CodebarAg\MFiles\DTO\ConfigWithCredentials;

beforeEach(function () {
    config([
        'm-files.cache_driver' => 'array',
        'm-files.auth.expiration' => 1800,
    ]);
});

test('fromArray builds config using Laravel config defaults for optional fields', function () {
    $dto = ConfigWithCredentials::fromArray([
        'url' => 'https://example.test',
        'vaultGuid' => 'vault-1',
        'username' => 'alice',
        'password' => 'secret',
    ]);

    expect($dto->url)->toBe('https://example.test')
        ->and($dto->vaultGuid)->toBe('vault-1')
        ->and($dto->username)->toBe('alice')
        ->and($dto->password)->toBe('secret')
        ->and($dto->cacheDriver)->toBe('array')
        ->and($dto->tokenTtlSeconds)->toBe(1800);
})->group('dto');

test('fromArray uses explicit tokenTtlSeconds and cacheDriver when provided', function () {
    $dto = ConfigWithCredentials::fromArray([
        'url' => 'https://example.test',
        'vaultGuid' => 'vault-1',
        'username' => 'alice',
        'password' => 'secret',
        'cacheDriver' => 'file',
        'tokenTtlSeconds' => 120,
    ]);

    expect($dto->cacheDriver)->toBe('file')
        ->and($dto->tokenTtlSeconds)->toBe(120);
})->group('dto');

test('fromArray rejects empty required string', function () {
    ConfigWithCredentials::fromArray([
        'url' => '',
        'vaultGuid' => 'v',
        'username' => 'u',
        'password' => 'p',
    ]);
})->throws(InvalidArgumentException::class, 'Config [url] must be a non-empty string.');

test('fromArray clamps tokenTtlSeconds below 1 to 1', function () {
    $dto = ConfigWithCredentials::fromArray([
        'url' => 'https://a.test',
        'vaultGuid' => 'v',
        'username' => 'u',
        'password' => 'p',
        'tokenTtlSeconds' => 0,
    ]);

    expect($dto->tokenTtlSeconds)->toBe(1);
})->group('dto');

test('constructor clamps tokenTtlSeconds below 1 to 1', function () {
    $dto = new ConfigWithCredentials(
        url: 'https://a.test',
        vaultGuid: 'v',
        username: 'u',
        password: 'p',
        cacheDriver: null,
        tokenTtlSeconds: 0,
    );

    expect($dto->tokenTtlSeconds)->toBe(1);
})->group('dto');

test('fromArray treats empty cacheDriver as null', function () {
    $dto = ConfigWithCredentials::fromArray([
        'url' => 'https://example.test',
        'vaultGuid' => 'vault-1',
        'username' => 'alice',
        'password' => 'secret',
        'cacheDriver' => '',
    ]);

    expect($dto->cacheDriver)->toBeNull();
})->group('dto');
