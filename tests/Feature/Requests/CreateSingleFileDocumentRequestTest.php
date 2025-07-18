<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use CodebarAg\MFiles\Connectors\MFilesConnector;
use CodebarAg\MFiles\DTO\Config\ConfigWithCredentials;
use CodebarAg\MFiles\DTO\Document;
use CodebarAg\MFiles\DTO\File;
use CodebarAg\MFiles\DTO\PropertyValue;
use CodebarAg\MFiles\Enums\MFDataTypeEnum;
use CodebarAg\MFiles\Fixtures\AuthenticationTokenFixture;
use CodebarAg\MFiles\Fixtures\CreateSingleFileDocumentFixture;
use CodebarAg\MFiles\Fixtures\UploadFileFixture;
use CodebarAg\MFiles\Requests\Authentication\GetAuthenticationToken;
use CodebarAg\MFiles\Requests\CreateSingleFileDocumentRequest;
use CodebarAg\MFiles\Requests\UploadFileRequest;
use Saloon\Laravel\Facades\Saloon;

test('can create single file document with basic parameters', function () {
    Saloon::fake([
        GetAuthenticationToken::class => new AuthenticationTokenFixture,
        UploadFileRequest::class => new UploadFileFixture,
        CreateSingleFileDocumentRequest::class => new CreateSingleFileDocumentFixture,
    ]);

    $config = new ConfigWithCredentials;
    $connector = new MFilesConnector($config);

    // Step 1: Upload the file
    $filePath = __DIR__.'/../../Fixtures/Files/test-1.pdf';
    $fileContent = file_get_contents($filePath);
    $fileName = 'test-1.pdf';

    $uploadedFile = $connector->send(new UploadFileRequest(
        fileContent: $fileContent,
        fileName: $fileName
    ))->dto();

    // Step 2: Create document with uploaded file
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
    Saloon::fake([
        GetAuthenticationToken::class => new AuthenticationTokenFixture,
        UploadFileRequest::class => new UploadFileFixture,
        CreateSingleFileDocumentRequest::class => new CreateSingleFileDocumentFixture,
    ]);

    $config = new ConfigWithCredentials;
    $connector = new MFilesConnector($config);

    // Step 1: Upload the file
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

    // Step 2: Create document with uploaded file
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
    Saloon::fake([
        GetAuthenticationToken::class => new AuthenticationTokenFixture,
        UploadFileRequest::class => new UploadFileFixture,
        CreateSingleFileDocumentRequest::class => new CreateSingleFileDocumentFixture,
    ]);

    $config = new ConfigWithCredentials;
    $connector = new MFilesConnector($config);

    // Step 1: Upload the file
    $filePath = __DIR__.'/../../Fixtures/Files/test-1.pdf';
    $fileContent = file_get_contents($filePath);
    $fileName = 'test-1.pdf';

    $uploadedFile = $connector->send(new UploadFileRequest(
        fileContent: $fileContent,
        fileName: $fileName
    ))->dto();

    // Step 2: Create document with uploaded file
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
