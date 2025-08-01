<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use CodebarAg\MFiles\Connectors\MFilesConnector;
use CodebarAg\MFiles\DTO\Config\ConfigWithCredentials;
use CodebarAg\MFiles\DTO\Document;
use CodebarAg\MFiles\DTO\File;
use CodebarAg\MFiles\DTO\PropertyValue;
use CodebarAg\MFiles\Enums\MFDataTypeEnum;
use CodebarAg\MFiles\Requests\CreateSingleFileDocumentRequest;
use CodebarAg\MFiles\Requests\LogInToVaultRequest;
use CodebarAg\MFiles\Requests\UploadFileRequest;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;

test('can create single file document with basic parameters', function () {

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
        cacheDriver: 'array'
    );
    $connector = new MFilesConnector($config);

    $filePath = __DIR__.'/../../Fixtures/Files/test-1.pdf';
    $fileContent = file_get_contents($filePath);
    $fileName = 'test-1.pdf';

    $uploadedFile = $connector->send(new UploadFileRequest(
        fileContent: $fileContent,
        fileName: $fileName
    ))->dto();
    $document = $connector->send(new CreateSingleFileDocumentRequest(
        title: 'Sample Document',
        file: $uploadedFile
    ))->dto();

    expect($document)->toBeInstanceOf(Document::class);
    expect($document->id)->toBe(123);
    expect($document->title)->toBe('Sample Document');
    expect($document->objectType)->toBeNull(); // objectType is null from fixture
    expect($document->objectTypeId)->toBe(0);
    expect($document->version)->toBe('1');
    expect($document->isCheckedOut)->toBeFalse();
    expect($document->isDeleted)->toBeFalse();
});

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

    $uploadedFile = $connector->send(new UploadFileRequest(
        fileContent: $fileContent,
        fileName: $fileName
    ))->dto();

    $propertyValues = [
        new PropertyValue(0, MFDataTypeEnum::TEXT, 'Custom Title'),
        new PropertyValue(5, MFDataTypeEnum::DATE, '2024-01-01'),
    ];
    $document = $connector->send(new CreateSingleFileDocumentRequest(
        title: 'Custom Document',
        file: $uploadedFile,
        propertyValues: $propertyValues
    ))->dto();

    expect($document)->toBeInstanceOf(Document::class);
    expect($document->id)->toBe(123);
    expect($document->title)->toBe('Sample Document');
});

test('throws exception when title is empty', function () {
    expect(fn () => new CreateSingleFileDocumentRequest(
        title: '',
        file: ['ID' => 1, 'Name' => 'test.pdf']
    ))->toThrow(\InvalidArgumentException::class, 'Title is required');
});

test('throws exception when file data is empty', function () {
    expect(fn () => new CreateSingleFileDocumentRequest(
        title: 'Valid Title',
        file: []
    ))->toThrow(\InvalidArgumentException::class, 'File data is required');
});

test('can access file properties from document', function () {
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

    $uploadedFile = $connector->send(new UploadFileRequest(
        fileContent: $fileContent,
        fileName: $fileName
    ))->dto();
    $document = $connector->send(new CreateSingleFileDocumentRequest(
        title: 'Document with File',
        file: $uploadedFile
    ))->dto();

    expect($document->files)->not->toBeNull();
    expect($document->files->count())->toBe(1);

    $file = $document->files->first();
    expect($file)->toBeInstanceOf(File::class);
    expect($file->id)->toBe(456);
    expect($file->name)->toBe('test-1.pdf');
    expect($file->extension)->toBe('pdf');
    expect($file->size)->toBe(8600);
    expect($file->lastModified)->toBeInstanceOf(CarbonImmutable::class);
});
