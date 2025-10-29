<?php

declare(strict_types=1);

use CodebarAg\MFiles\DTO\MFilesError;
use CodebarAg\MFiles\Responses\ErrorResponse;
use Saloon\Enums\Method;
use Saloon\Http\Connector;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Request;
use Saloon\Laravel\Facades\Saloon;

it('creates MFilesError from response json', function () {
    $json = [
        'ErrorCode' => 'ERROR_001',
        'Status' => 403,
        'URL' => '/session/vaults',
        'Method' => 'GET',
        'Exception' => [
            'Name' => 'UnauthorizedAccessException',
            'Message' => 'Login to application failed',
        ],
        'Stack' => 'Stack trace here',
    ];

    $request = new class extends Request
    {
        protected Method $method = Method::GET;

        public function resolveEndpoint(): string
        {
            return '/test';
        }
    };

    Saloon::fake([
        get_class($request) => MockResponse::make(status: 403, body: json_encode($json)),
    ]);

    $connector = new class extends Connector
    {
        public function resolveBaseUrl(): string
        {
            return 'https://test.example.com';
        }
    };

    $response = $connector->send($request);

    $error = ErrorResponse::createDtoFromResponse($response);

    expect($error)->toBeInstanceOf(MFilesError::class);
    expect($error->errorCode)->toBe('ERROR_001');
    expect($error->status)->toBe(403);
    expect($error->url)->toBe('/session/vaults');
    expect($error->method)->toBe('GET');
    expect($error->exceptionName)->toBe('UnauthorizedAccessException');
    expect($error->exceptionMessage)->toBe('Login to application failed');
    expect($error->stack)->toBe('Stack trace here');
});

it('handles response with missing optional fields', function () {
    $json = [
        'Status' => 400,
        'URL' => '/test',
        'Method' => 'POST',
        'Exception' => [
            'Name' => 'BadRequest',
            'Message' => 'Bad request',
        ],
    ];

    $request = new class extends Request
    {
        protected Method $method = Method::GET;

        public function resolveEndpoint(): string
        {
            return '/test';
        }
    };

    Saloon::fake([
        get_class($request) => MockResponse::make(status: 400, body: json_encode($json)),
    ]);

    $connector = new class extends Connector
    {
        public function resolveBaseUrl(): string
        {
            return 'https://test.example.com';
        }
    };

    $response = $connector->send($request);

    $error = ErrorResponse::createDtoFromResponse($response);

    expect($error->errorCode)->toBe('');
    expect($error->stack)->toBeNull();
});

it('handles empty exception object', function () {
    $json = [
        'Status' => 500,
        'URL' => '/api',
        'Method' => 'GET',
        'Exception' => [],
    ];

    $request = new class extends Request
    {
        protected Method $method = Method::GET;

        public function resolveEndpoint(): string
        {
            return '/test';
        }
    };

    Saloon::fake([
        get_class($request) => MockResponse::make(status: 500, body: json_encode($json)),
    ]);

    $connector = new class extends Connector
    {
        public function resolveBaseUrl(): string
        {
            return 'https://test.example.com';
        }
    };

    $response = $connector->send($request);

    $error = ErrorResponse::createDtoFromResponse($response);

    expect($error->exceptionName)->toBe('');
    expect($error->exceptionMessage)->toBe('');
});
