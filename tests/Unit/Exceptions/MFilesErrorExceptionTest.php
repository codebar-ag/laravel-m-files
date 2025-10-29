<?php

declare(strict_types=1);

use CodebarAg\MFiles\DTO\MFilesError;
use CodebarAg\MFiles\Exceptions\MFilesErrorException;

it('creates exception with MFilesError', function () {
    $error = new MFilesError(
        errorCode: 'ERROR_001',
        status: 403,
        url: '/session/vaults',
        method: 'GET',
        exceptionName: 'UnauthorizedAccessException',
        exceptionMessage: 'Login to application failed',
        stack: 'Stack trace here'
    );

    $exception = new MFilesErrorException($error);

    expect($exception->error)->toBe($error);
    expect($exception->getMessage())->toBe('Login to application failed');
    expect($exception->getCode())->toBe(403);
});

it('extends base Exception class', function () {
    $error = new MFilesError(
        errorCode: 'ERROR_002',
        status: 404,
        url: '/objects/123',
        method: 'GET',
        exceptionName: 'NotFoundException',
        exceptionMessage: 'Object not found',
        stack: null
    );

    $exception = new MFilesErrorException($error);

    expect($exception)->toBeInstanceOf(Exception::class);
});

it('can access error properties through exception', function () {
    $error = new MFilesError(
        errorCode: 'ERROR_003',
        status: 500,
        url: '/api/endpoint',
        method: 'POST',
        exceptionName: 'InternalServerError',
        exceptionMessage: 'Internal server error occurred',
        stack: 'Detailed stack trace'
    );

    $exception = new MFilesErrorException($error);

    expect($exception->error->errorCode)->toBe('ERROR_003');
    expect($exception->error->status)->toBe(500);
    expect($exception->error->url)->toBe('/api/endpoint');
    expect($exception->error->method)->toBe('POST');
    expect($exception->error->exceptionName)->toBe('InternalServerError');
    expect($exception->error->exceptionMessage)->toBe('Internal server error occurred');
    expect($exception->error->stack)->toBe('Detailed stack trace');
});
