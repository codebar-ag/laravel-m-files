<?php

declare(strict_types=1);

use CodebarAg\MFiles\DTO\MFilesError;

it('creates instance with all properties', function () {
    $error = new MFilesError(
        errorCode: 'ERROR_001',
        status: 403,
        url: '/session/vaults',
        method: 'GET',
        exceptionName: 'UnauthorizedAccessException',
        exceptionMessage: 'Login to application failed',
        stack: 'Stack trace here'
    );

    expect($error->errorCode)->toBe('ERROR_001');
    expect($error->status)->toBe(403);
    expect($error->url)->toBe('/session/vaults');
    expect($error->method)->toBe('GET');
    expect($error->exceptionName)->toBe('UnauthorizedAccessException');
    expect($error->exceptionMessage)->toBe('Login to application failed');
    expect($error->stack)->toBe('Stack trace here');
});

it('creates instance with null stack', function () {
    $error = new MFilesError(
        errorCode: 'ERROR_002',
        status: 404,
        url: '/objects/123',
        method: 'GET',
        exceptionName: 'NotFoundException',
        exceptionMessage: 'Object not found',
        stack: null
    );

    expect($error->stack)->toBeNull();
});

it('creates instance from array with all properties', function () {
    $data = [
        'ErrorCode' => 'ERROR_003',
        'Status' => 500,
        'URL' => '/api/endpoint',
        'Method' => 'POST',
        'Exception' => [
            'Name' => 'InternalServerError',
            'Message' => 'Internal server error occurred',
        ],
        'Stack' => 'Detailed stack trace',
    ];

    $error = MFilesError::fromArray($data);

    expect($error->errorCode)->toBe('ERROR_003');
    expect($error->status)->toBe(500);
    expect($error->url)->toBe('/api/endpoint');
    expect($error->method)->toBe('POST');
    expect($error->exceptionName)->toBe('InternalServerError');
    expect($error->exceptionMessage)->toBe('Internal server error occurred');
    expect($error->stack)->toBe('Detailed stack trace');
});

it('creates instance from array with missing optional fields', function () {
    $data = [
        'Status' => 400,
        'URL' => '/test',
        'Method' => 'GET',
        'Exception' => [
            'Name' => 'BadRequest',
            'Message' => 'Bad request',
        ],
    ];

    $error = MFilesError::fromArray($data);

    expect($error->errorCode)->toBe('');
    expect($error->status)->toBe(400);
    expect($error->url)->toBe('/test');
    expect($error->method)->toBe('GET');
    expect($error->exceptionName)->toBe('BadRequest');
    expect($error->exceptionMessage)->toBe('Bad request');
    expect($error->stack)->toBeNull();
});

it('creates instance from array with empty exception object', function () {
    $data = [
        'ErrorCode' => 'ERROR_004',
        'Status' => 401,
        'URL' => '/auth',
        'Method' => 'POST',
        'Exception' => [],
        'Stack' => null,
    ];

    $error = MFilesError::fromArray($data);

    expect($error->exceptionName)->toBe('');
    expect($error->exceptionMessage)->toBe('');
});

it('converts instance to array correctly', function () {
    $error = new MFilesError(
        errorCode: 'ERROR_005',
        status: 422,
        url: '/validate',
        method: 'PUT',
        exceptionName: 'ValidationError',
        exceptionMessage: 'Validation failed',
        stack: 'Error stack'
    );

    $array = $error->toArray();

    expect($array)->toBe([
        'errorCode' => 'ERROR_005',
        'status' => 422,
        'url' => '/validate',
        'method' => 'PUT',
        'exceptionName' => 'ValidationError',
        'exceptionMessage' => 'Validation failed',
        'stack' => 'Error stack',
    ]);
});

it('converts instance to array with null stack', function () {
    $error = new MFilesError(
        errorCode: 'ERROR_006',
        status: 500,
        url: '/test',
        method: 'DELETE',
        exceptionName: 'ServerError',
        exceptionMessage: 'Server error',
        stack: null
    );

    $array = $error->toArray();

    expect($array['stack'])->toBeNull();
});
