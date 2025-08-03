<?php

declare(strict_types=1);

use CodebarAg\MFiles\Connectors\MFilesConnector;
use CodebarAg\MFiles\DTO\ConfigWithCredentials;
use CodebarAg\MFiles\DTO\SetProperty;
use CodebarAg\MFiles\Enums\MFDataTypeEnum;
use CodebarAg\MFiles\Requests\LogInToVaultRequest;
use CodebarAg\MFiles\Requests\SetPropertiesRequest;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;

test('can set properties using object type and object id', function () {

    Saloon::fake([
        LogInToVaultRequest::class => MockResponse::fixture('set-properties-login-object'),
        SetPropertiesRequest::class => MockResponse::fixture('set-properties'),
    ]);

    $config = new ConfigWithCredentials(
        url: config('m-files.auth.url'),
        vaultGuid: config('m-files.vault_guid'),
        username: config('m-files.auth.username'),
        password: config('m-files.auth.password'),
    );

    $connector = new MFilesConnector($config);

    $objectType = 140;
    $objectId = 1770;
    $objectVersion = -1;

    $propertyValues = [
        new SetProperty(1856, MFDataTypeEnum::BOOLEAN, true),
    ];

    dd(collect($propertyValues)->map(fn (SetProperty $propertyValue) => $propertyValue->toArray())->toArray());

    $request = new SetPropertiesRequest(
        $objectType,
        $objectId,
        $objectVersion,
        $propertyValues
    );

    $response = $connector->send($request);

    expect($response->status())->toBe(200);

})->group('set-properties');
