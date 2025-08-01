<?php

declare(strict_types=1);

use CodebarAg\MFiles\Connectors\MFilesConnector;
use CodebarAg\MFiles\DTO\Config\ConfigWithCredentials;
use CodebarAg\MFiles\DTO\Document;
use CodebarAg\MFiles\DTO\File;
use CodebarAg\MFiles\DTO\PropertyValue;
use CodebarAg\MFiles\Enums\MFDataTypeEnum;
use CodebarAg\MFiles\Requests\CreateSingleFileDocumentRequest;
use CodebarAg\MFiles\Requests\LogInToVaultRequest;
use CodebarAg\MFiles\Requests\UploadFileRequest;
use Illuminate\Support\Arr;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;

test('can create single file document with custom property values', function () {
    /*     Saloon::fake([
            LogInToVaultRequest::class => MockResponse::fixture('login-to-vault'),
            UploadFileRequest::class => MockResponse::fixture('upload-file'),
            CreateSingleFileDocumentRequest::class => MockResponse::fixture('create-single-file-document'),
        ]); */

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
        new PropertyValue(0, MFDataTypeEnum::TEXT, Arr::get($fileUpload, 'Title')),
        new PropertyValue(38, MFDataTypeEnum::LOOKUP, 121),
        new PropertyValue(39, MFDataTypeEnum::LOOKUP, 474),
        new PropertyValue(100, MFDataTypeEnum::LOOKUP, 207),
    ];

    $document = $connector->send(new CreateSingleFileDocumentRequest(
        title: 'Custom Document',
        files: [$fileUpload],
        propertyValues: $propertyValues
    ));

    dd($document->status(), $document->json());

    expect($document)->toBeInstanceOf(Document::class);
    expect($document->id)->toBe(123);
    expect($document->title)->toBe('Custom Document');
})->group('create-single-file-document');
