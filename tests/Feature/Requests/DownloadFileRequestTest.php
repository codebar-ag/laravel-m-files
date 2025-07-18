<?php

declare(strict_types=1);

use CodebarAg\MFiles\Connectors\MFilesConnector;
use CodebarAg\MFiles\DTO\Config\ConfigWithCredentials;
use CodebarAg\MFiles\DTO\DownloadedFile;
use CodebarAg\MFiles\Requests\DownloadFileRequest;
use CodebarAg\MFiles\Requests\GetDocumentsRequest;
use CodebarAg\MFiles\Requests\Authentication\GetAuthenticationToken;
use CodebarAg\MFiles\Fixtures\AuthenticationTokenFixture;
use CodebarAg\MFiles\Fixtures\DownloadFileFixture;
use CodebarAg\MFiles\Fixtures\DocumentsFixture;
use Saloon\Http\Response;
use Saloon\Laravel\Facades\Saloon;

beforeEach(function () {
    Saloon::fake([
        GetAuthenticationToken::class => new AuthenticationTokenFixture,
        GetDocumentsRequest::class => new DocumentsFixture,
        DownloadFileRequest::class => new DownloadFileFixture,
    ]);

    $this->config = new ConfigWithCredentials();

    $this->connector = new MFilesConnector($this->config);
});

it('can download a file from a document', function () {
    $documents = $this->connector->send(new GetDocumentsRequest())->dto();
    
    expect($documents->count())->toBeGreaterThan(0);
    
    $documentWithFiles = $documents->items->first(function ($document) {
        return $document->files && $document->files->count() > 0;
    });
    
    expect($documentWithFiles)->not->toBeNull();
    expect($documentWithFiles->files)->toHaveCount(1);
    
    $file = $documentWithFiles->files->first();
    expect($file->id)->toBe(101);
    expect($file->name)->toBe('sample-document-1');
    expect($file->extension)->toBe('pdf');
    
    $downloadRequest = new DownloadFileRequest(
        objectId: $documentWithFiles->id,
        fileId: $file->id
    );
    
    $response = $this->connector->send($downloadRequest);
    $downloadedFile = $response->dto();
    
    expect($response)->toBeInstanceOf(Response::class);
    expect($downloadedFile)->toBeInstanceOf(DownloadedFile::class);
    expect($downloadedFile->content)->toBe('This is the content of sample-document-1.pdf file from M-Files. It contains sample data for testing purposes.');
    expect($downloadedFile->name)->toBe('sample-document-1');
    expect($downloadedFile->extension)->toBe('pdf');
    expect($downloadedFile->size)->toBe(1024);
    expect($downloadedFile->contentType)->toBe('application/pdf');
    expect($downloadedFile->name . '.' . $downloadedFile->extension)->toBe('sample-document-1.pdf');
});



it('can download a file with optional parameters', function () {
    $documents = $this->connector->send(new GetDocumentsRequest())->dto();
    $document = $documents->items->first();
    $file = $document->files->first();
    
    $request = new DownloadFileRequest(
        objectId: $document->id,
        fileId: $file->id,
        objectTypeId: $document->objectTypeId,
        includeDeleted: false
    );

    $response = $this->connector->send($request);
    $downloadedFile = $response->dto();

    expect($downloadedFile)->toBeInstanceOf(DownloadedFile::class);
    expect($downloadedFile->content)->toBe('This is the content of sample-document-1.pdf file from M-Files. It contains sample data for testing purposes.');
    expect($downloadedFile->name)->toBe('sample-document-1');
    expect($downloadedFile->extension)->toBe('pdf');
    expect($downloadedFile->size)->toBe(1024);
    expect($downloadedFile->contentType)->toBe('application/pdf');
    expect($downloadedFile->name . '.' . $downloadedFile->extension)->toBe('sample-document-1.pdf');
}); 