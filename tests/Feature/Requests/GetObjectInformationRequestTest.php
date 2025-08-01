<?php

declare(strict_types=1);

use CodebarAg\MFiles\Connectors\MFilesConnector;
use CodebarAg\MFiles\DTO\ConfigWithCredentials;
use CodebarAg\MFiles\DTO\ObjectProperties;
use CodebarAg\MFiles\Requests\GetObjectInformationRequest;
use CodebarAg\MFiles\Requests\LogInToVaultRequest;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;

test('can get object properties using object type and object id', function () {

    Saloon::fake([
        LogInToVaultRequest::class => MockResponse::fixture('get-object-information-login-to-vault'),
        GetObjectInformationRequest::class => MockResponse::fixture('get-object-information'),
    ]);

    $config = new ConfigWithCredentials(
        url: config('m-files.auth.url'),
        vaultGuid: config('m-files.vault_guid'),
        username: config('m-files.auth.username'),
        password: config('m-files.auth.password'),
    );

    $connector = new MFilesConnector($config);

    $objectType = 0;
    $objectId = 1090;
    $objectVersion = 10;

    $request = new GetObjectInformationRequest($objectType, $objectId, $objectVersion);

    expect($request->objectType)->toBe($objectType);
    expect($request->objectId)->toBe($objectId);
    expect($request->objectVersion)->toBe($objectVersion);

    $response = $connector->send($request);
    expect($response->status())->toBe(200);
    expect($response->dto())->toBeInstanceOf(ObjectProperties::class);
})->group('object-information', 'get-object-information');
