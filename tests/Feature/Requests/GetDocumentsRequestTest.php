<?php

declare(strict_types=1);

use CodebarAg\MFiles\Connectors\MFilesConnector;
use CodebarAg\MFiles\DTO\Config\ConfigWithCredentials;
use CodebarAg\MFiles\DTO\Document;
use CodebarAg\MFiles\DTO\Documents;
use CodebarAg\MFiles\Fixtures\AuthenticationTokenFixture;
use CodebarAg\MFiles\Fixtures\DocumentsFixture;
use CodebarAg\MFiles\Requests\Authentication\GetAuthenticationToken;
use CodebarAg\MFiles\Requests\GetDocumentsRequest;
use Saloon\Laravel\Facades\Saloon;

test('can get documents with default parameters', function () {
    Saloon::fake([
        GetAuthenticationToken::class => new AuthenticationTokenFixture,
        GetDocumentsRequest::class => new DocumentsFixture,
    ]);

    $config = new ConfigWithCredentials;
    $connector = new MFilesConnector($config);

    $documents = $connector->send(new GetDocumentsRequest)->dto();

    expect($documents)->toBeInstanceOf(Documents::class);
    expect($documents->count())->toBe(2);
    expect($documents->totalCount)->toBe(2);
    expect($documents->page)->toBe(1);
    expect($documents->pageSize)->toBe(10);
});

test('can get documents with filtering parameters', function () {
    Saloon::fake([
        GetAuthenticationToken::class => new AuthenticationTokenFixture,
        GetDocumentsRequest::class => new DocumentsFixture,
    ]);

    $config = new ConfigWithCredentials;
    $connector = new MFilesConnector($config);

    $documents = $connector->send(new GetDocumentsRequest(
        page: 1,
        pageSize: 5,
        searchString: 'Sample',
        objectTypeId: 0,
        includeDeleted: false,
        includeSubfolders: true,
        sortBy: 'Title',
        sortDirection: 'asc'
    ))->dto();

    expect($documents)->toBeInstanceOf(Documents::class);
    expect($documents->count())->toBe(2);
});

test('can access individual documents', function () {
    Saloon::fake([
        GetAuthenticationToken::class => new AuthenticationTokenFixture,
        GetDocumentsRequest::class => new DocumentsFixture,
    ]);

    $config = new ConfigWithCredentials;
    $connector = new MFilesConnector($config);

    $documents = $connector->send(new GetDocumentsRequest)->dto();

    $firstDocument = $documents->first();

    expect($firstDocument)->toBeInstanceOf(Document::class);
    expect($firstDocument->id)->toBe(1);
    expect($firstDocument->title)->toBe('Sample Document 1');
    expect($firstDocument->objectType)->toBe('154');
    expect($firstDocument->isCheckedOut)->toBeFalse();

    $lastDocument = $documents->last();

    expect($lastDocument)->toBeInstanceOf(Document::class);
    expect($lastDocument->id)->toBe(2);
    expect($lastDocument->title)->toBe('Sample Document 2');
    expect($lastDocument->isCheckedOut)->toBeFalse();
    expect($lastDocument->checkedOutBy)->toBeNull();
});

test('can iterate through documents', function () {
    Saloon::fake([
        GetAuthenticationToken::class => new AuthenticationTokenFixture,
        GetDocumentsRequest::class => new DocumentsFixture,
    ]);

    $config = new ConfigWithCredentials;
    $connector = new MFilesConnector($config);

    $documents = $connector->send(new GetDocumentsRequest)->dto();

    $titles = [];
    foreach ($documents->items as $document) {
        $titles[] = $document->title;
    }

    expect($titles)->toBe(['Sample Document 1', 'Sample Document 2']);
});
