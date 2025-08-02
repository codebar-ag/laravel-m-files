<?php

declare(strict_types=1);

use CodebarAg\MFiles\Connectors\MFilesConnector;
use CodebarAg\MFiles\DTO\ConfigWithCredentials;
use CodebarAg\MFiles\DTO\DownloadedFile;
use CodebarAg\MFiles\Requests\DownloadFileRequest;
use CodebarAg\MFiles\Requests\LogInToVaultRequest;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Response;
use Saloon\Laravel\Facades\Saloon;

it('can download a file from a document', function () {
    Saloon::fake([
        LogInToVaultRequest::class => MockResponse::fixture('download-file-login-to-vault'),
        DownloadFileRequest::class => MockResponse::fixture('download-file'),
    ]);

    $this->config = new ConfigWithCredentials(
        url: config('m-files.auth.url'),
        vaultGuid: config('m-files.vault_guid'),
        username: config('m-files.auth.username'),
        password: config('m-files.auth.password'),
    );

    $this->connector = new MFilesConnector($this->config);

    $downloadRequest = new DownloadFileRequest(
        objectType: 0,
        objectId: 1090,
        objectVersion: 10,
        fileId: 1116,
    );

    $response = $this->connector->send($downloadRequest);
    $downloadedFile = $response->dto();

    expect($response)->toBeInstanceOf(Response::class);
    expect($downloadedFile)->toBeInstanceOf(DownloadedFile::class);
    expect($downloadedFile->content)->not->toBeEmpty();
    expect($downloadedFile->name)->not->toBeNull();
    expect($downloadedFile->extension)->not->toBeNull();
    expect($downloadedFile->size)->toBeGreaterThan(0);
    expect($downloadedFile->contentType)->not->toBeEmpty();
    expect($downloadedFile->name.'.'.$downloadedFile->extension)->toContain('.');
})->group('download-file');
