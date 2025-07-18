<?php

declare(strict_types=1);

use CodebarAg\MFiles\Connectors\MFilesConnector;
use CodebarAg\MFiles\DTO\Config\ConfigWithCredentials;
use CodebarAg\MFiles\Fixtures\AuthenticationTokenFixture;
use CodebarAg\MFiles\Fixtures\UploadFileFixture;
use CodebarAg\MFiles\Requests\Authentication\GetAuthenticationToken;
use CodebarAg\MFiles\Requests\UploadFileRequest;
use Saloon\Laravel\Facades\Saloon;

test('can upload file', function () {
    Saloon::fake([
        GetAuthenticationToken::class => new AuthenticationTokenFixture,
        UploadFileRequest::class => new UploadFileFixture,
    ]);

    $config = new ConfigWithCredentials;
    $connector = new MFilesConnector($config);

    $filePath = __DIR__.'/../../Fixtures/Files/test-1.pdf';
    $fileContent = file_get_contents($filePath);
    $fileName = 'test-1.pdf';

    $result = $connector->send(new UploadFileRequest(
        fileContent: $fileContent,
        fileName: $fileName
    ))->dto();

    expect($result)->toBeArray();
    expect($result['ID'])->toBe(456);
    expect($result['Title'])->toBe('test-1');
    expect($result['Extension'])->toBe('pdf');
    expect($result)->not->toHaveKey('FileInformationType');
});

test('throws exception when file content is empty', function () {
    expect(fn () => new UploadFileRequest(
        fileContent: '',
        fileName: 'test.pdf'
    ))->toThrow(\InvalidArgumentException::class, 'File content is required');
});

test('throws exception when file name is empty', function () {
    expect(fn () => new UploadFileRequest(
        fileContent: 'test content',
        fileName: ''
    ))->toThrow(\InvalidArgumentException::class, 'File name is required');
});
