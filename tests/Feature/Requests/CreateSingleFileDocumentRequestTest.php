<?php

declare(strict_types=1);

use CodebarAg\MFiles\Connectors\MFilesConnector;
use CodebarAg\MFiles\DTO\ConfigWithCredentials;
use CodebarAg\MFiles\DTO\ObjectProperties;
use CodebarAg\MFiles\DTO\SetProperty;
use CodebarAg\MFiles\Enums\MFDataTypeEnum;
use CodebarAg\MFiles\Requests\CreateSingleFileDocumentRequest;
use CodebarAg\MFiles\Requests\LogInToVaultRequest;
use CodebarAg\MFiles\Requests\UploadFileRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;

test('can create single file document with custom property values', function () {
    Saloon::fake([
        LogInToVaultRequest::class => MockResponse::fixture('create-single-file-document-login-to-vault'),
        UploadFileRequest::class => MockResponse::fixture('upload-file'),
        CreateSingleFileDocumentRequest::class => MockResponse::fixture('create-single-file-document'),
    ]);

    $config = new ConfigWithCredentials(
        url: config('m-files.auth.url'),
        vaultGuid: config('m-files.vault_guid'),
        username: config('m-files.auth.username'),
        password: config('m-files.auth.password'),
    );

    $connector = new MFilesConnector($config);

    $filePath = __DIR__.'/../../Fixtures/Files/test-1.pdf';
    $fileContent = file_get_contents($filePath);
    $fileName = 'test-1.pdf';

    $fileUpload = $connector->send(new UploadFileRequest(
        fileContent: $fileContent,
        fileName: $fileName
    ))->dto();

    $propertyValues = [
        new SetProperty(0, MFDataTypeEnum::TEXT, Arr::get($fileUpload, 'Title')),
        new SetProperty(38, MFDataTypeEnum::LOOKUP, 121),
        new SetProperty(39, MFDataTypeEnum::LOOKUP, 474),
        new SetProperty(100, MFDataTypeEnum::LOOKUP, 207),
    ];

    $objecttProperty = $connector->send(new CreateSingleFileDocumentRequest(
        title: 'Custom Document',
        files: [$fileUpload],
        propertyValues: $propertyValues
    ))->dto();

    expect($objecttProperty)->toBeInstanceOf(ObjectProperties::class);
    expect($objecttProperty->objectId)->toBe(1102);
    expect($objecttProperty->objectTypeId)->toBe(0);
    expect($objecttProperty->objectVersionId)->toBe(1);
    expect($objecttProperty->properties)->toBeInstanceOf(Collection::class);
    expect($objecttProperty->files)->toBeInstanceOf(Collection::class);
})->group('create-single-file-document');
