<?php

declare(strict_types=1);

use CodebarAg\MFiles\Connectors\MFilesConnector;
use CodebarAg\MFiles\DTO\ConfigWithCredentials;
use CodebarAg\MFiles\Requests\LogInToVaultRequest;
use CodebarAg\MFiles\Requests\UploadFileRequest;
use Illuminate\Support\Arr;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;

test('can upload file', function () {

    Saloon::fake([
        LogInToVaultRequest::class => MockResponse::fixture('upload-file-login-to-vault'),
        UploadFileRequest::class => MockResponse::fixture('upload-file'),
    ]);

    $configuration = new ConfigWithCredentials(
        url: config('m-files.auth.url'),
        vaultGuid: config('m-files.vault_guid'),
        username: config('m-files.auth.username'),
        password: config('m-files.auth.password'),
    );

    $connector = new MFilesConnector($configuration);

    $filePath = __DIR__.'/../../Fixtures/Files/test-1.pdf';
    $fileContent = file_get_contents($filePath);
    $fileName = 'test-1.pdf';

    $response = $connector->send(new UploadFileRequest(
        fileContent: $fileContent,
        fileName: $fileName
    ));

    expect(Arr::get($response->dto(), 'UploadID'))->toBe(1);
    expect(Arr::get($response->dto(), 'Size'))->toBe(8785);
    expect(Arr::get($response->dto(), 'Title'))->toBe('test-1');
    expect(Arr::get($response->dto(), 'Extension'))->toBe('pdf');
})->group('upload-file');
