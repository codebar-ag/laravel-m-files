<?php

declare(strict_types=1);

use CodebarAg\MFiles\Connectors\MFilesConnector;
use CodebarAg\MFiles\DTO\Config\ConfigWithCredentials;
use CodebarAg\MFiles\Fixtures\AuthenticationTokenFixture;
use CodebarAg\MFiles\Fixtures\DocumentPropertiesFixture;
use CodebarAg\MFiles\Fixtures\DocumentsFixture;
use CodebarAg\MFiles\Requests\Authentication\GetAuthenticationToken;
use CodebarAg\MFiles\Requests\GetDocumentPropertiesRequest;
use CodebarAg\MFiles\Requests\GetDocumentsRequest;
use Saloon\Laravel\Facades\Saloon;

test('can get document properties using document id from documents list', function () {
    Saloon::fake([
        GetAuthenticationToken::class => new AuthenticationTokenFixture,
        GetDocumentsRequest::class => new DocumentsFixture,
        GetDocumentPropertiesRequest::class => new DocumentPropertiesFixture,
    ]);

    $config = new ConfigWithCredentials;
    $connector = new MFilesConnector($config);

    $documents = $connector->send(new GetDocumentsRequest)->dto();

    expect($documents)->toBeInstanceOf(\CodebarAg\MFiles\DTO\Documents::class);
    expect($documents->count())->toBe(2);

    $firstDocument = $documents->items->firstWhere('propertyID', 0);
    expect($firstDocument)->not->toBeNull();

    $documentId = $firstDocument->id;
    expect($documentId)->not->toBeNull();

    $request = new GetDocumentPropertiesRequest($documentId);
    expect($request->objectId)->toBe($documentId);

    $response = $connector->send($request);
    expect($response->status())->toBe(404);
});

test('can get document properties with filtering parameters using document id', function () {
    Saloon::fake([
        GetAuthenticationToken::class => new AuthenticationTokenFixture,
        GetDocumentsRequest::class => new DocumentsFixture,
        GetDocumentPropertiesRequest::class => new DocumentPropertiesFixture,
    ]);

    $config = new ConfigWithCredentials;
    $connector = new MFilesConnector($config);

    $documents = $connector->send(new GetDocumentsRequest)->dto();
    $firstDocument = $documents->items->firstWhere('propertyID', 0);
    $documentId = $firstDocument->id;

    $request = new GetDocumentPropertiesRequest(
        objectId: $documentId,
        objectTypeId: 0,
        includeDeleted: false
    );

    expect($request->objectId)->toBe($documentId);
    expect($request->objectTypeId)->toBe(0);
    expect($request->includeDeleted)->toBeFalse();

    $response = $connector->send($request);
    expect($response->status())->toBe(404);
});

test('can access document metadata from documents list', function () {
    Saloon::fake([
        GetAuthenticationToken::class => new AuthenticationTokenFixture,
        GetDocumentsRequest::class => new DocumentsFixture,
    ]);

    $config = new ConfigWithCredentials;
    $connector = new MFilesConnector($config);

    $documents = $connector->send(new GetDocumentsRequest)->dto();

    $firstDocument = $documents->items->firstWhere('propertyID', 0);

    $propertyIDs = $documents->items->pluck('propertyID')->unique()->values();

    expect($firstDocument->id)->not->toBeNull();
    expect($firstDocument->title)->not->toBeNull();
    expect($firstDocument->propertyID)->toBe(0);
    expect($firstDocument->version)->not->toBeNull();
    expect($firstDocument->isCheckedOut)->toBeFalse();
    expect($firstDocument->isDeleted)->toBeFalse();

    expect($propertyIDs)->not->toBeEmpty();
});

test('can iterate through documents and get their IDs', function () {
    Saloon::fake([
        GetAuthenticationToken::class => new AuthenticationTokenFixture,
        GetDocumentsRequest::class => new DocumentsFixture,
    ]);

    $config = new ConfigWithCredentials;
    $connector = new MFilesConnector($config);

    $documents = $connector->send(new GetDocumentsRequest)->dto();

    $documentIds = [];
    foreach ($documents->items->take(5) as $document) {
        $documentIds[] = $document->id;
    }

    expect($documentIds)->toHaveCount(2);
    expect($documentIds[0])->toBe(1);
    expect($documentIds[1])->toBe(2);
});

test('can create GetDocumentPropertiesRequest with document ID from documents list', function () {
    Saloon::fake([
        GetAuthenticationToken::class => new AuthenticationTokenFixture,
        GetDocumentsRequest::class => new DocumentsFixture,
    ]);

    $config = new ConfigWithCredentials;
    $connector = new MFilesConnector($config);

    $documents = $connector->send(new GetDocumentsRequest)->dto();
    $firstDocument = $documents->items->firstWhere('propertyID', 0);
    $documentId = $firstDocument->id;

    $request = new GetDocumentPropertiesRequest($documentId);

    expect($request->objectId)->toBe($documentId);
    expect($request->objectTypeId)->toBeNull();
    expect($request->includeDeleted)->toBeNull();
});
