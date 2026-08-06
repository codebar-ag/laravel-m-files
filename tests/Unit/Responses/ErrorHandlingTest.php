<?php

declare(strict_types=1);

use CodebarAg\MFiles\DTO\MFilesError;
use CodebarAg\MFiles\Exceptions\MFilesErrorException;
use CodebarAg\MFiles\Requests\GetObjectInformationRequest;
use CodebarAg\MFiles\Responses\ErrorResponse;
use CodebarAg\MFiles\Responses\ObjectPropertiesResponse;
use Saloon\Http\Faking\MockResponse;

/**
 * @param  array<string, string>  $headers
 */
function errorFor(mixed $body, int $status = 500, array $headers = []): MFilesError
{
    return ErrorResponse::createDtoFromResponse(
        mFilesSend(MockResponse::make($body, $status, $headers))
    );
}

describe('non-JSON error bodies', function () {
    test('an HTML error page from a proxy does not blow up the error path', function () {
        // Response::json() decodes with JSON_THROW_ON_ERROR, so an HTML body used to
        // raise a JsonException from inside the failure handler and bury the real
        // problem. IIS, load balancers and SSO interstitials all answer like this.
        $error = errorFor('<html><head><title>502 Bad Gateway</title></head><body><h1>502 Bad Gateway</h1></body></html>', 502);

        expect($error->status)->toBe(502)
            ->and($error->exceptionMessage)->toContain('502 Bad Gateway');
    });

    test('an empty body still yields an actionable status', function () {
        $error = errorFor('', 503);

        expect($error->status)->toBe(503)
            ->and($error->exceptionMessage)->toContain('503');
    });

    test('a JSON scalar body is treated as non-JSON', function () {
        $error = errorFor('"just a string"', 500);

        expect($error->status)->toBe(500);
    });

    test('malformed JSON does not throw', function () {
        $error = errorFor('{"Status": 500, ', 500);

        expect($error->status)->toBe(500);
    });
});

describe('M-Files error payloads', function () {
    test('parses the documented error shape', function () {
        $error = errorFor([
            'Status' => 403,
            'URL' => '/session/vaults',
            'Method' => 'GET',
            'Exception' => ['Name' => 'UnauthorizedAccessException', 'Message' => 'Login to application failed'],
            'Stack' => 'Fehlerreferenz-ID: abc',
        ], 403);

        expect($error->status)->toBe(403)
            ->and($error->url)->toBe('/session/vaults')
            ->and($error->method)->toBe('GET')
            ->and($error->exceptionName)->toBe('UnauthorizedAccessException')
            ->and($error->exceptionMessage)->toBe('Login to application failed')
            ->and($error->stack)->toBe('Fehlerreferenz-ID: abc');
    });

    test('accepts a Stack sent as a list of frames', function () {
        // Typed ?string; a list used to raise a TypeError inside the error handler.
        $error = errorFor([
            'Status' => 500,
            'Stack' => ['at Foo.Bar()', 'at Baz.Qux()'],
        ], 500);

        expect($error->stack)->toBe("at Foo.Bar()\nat Baz.Qux()");
    });

    test('accepts a Stack sent as structured frames', function () {
        $error = errorFor([
            'Status' => 500,
            'Stack' => [['method' => 'Foo'], ['method' => 'Bar']],
        ], 500);

        expect($error->stack)->toBeString()
            ->and($error->stack)->toContain('Foo');
    });

    test('accepts a numeric-string Status', function () {
        $error = errorFor(['Status' => '404', 'Message' => 'missing'], 404);

        expect($error->status)->toBe(404);
    });

    test('accepts a non-string ErrorCode', function () {
        $error = errorFor(['Status' => 400, 'ErrorCode' => 1234], 400);

        expect($error->errorCode)->toBe('1234');
    });

    test('falls back to the top-level Message when the Exception envelope is absent', function () {
        $error = errorFor([
            'Status' => 409,
            'Message' => 'Object is checked out',
            'ExceptionName' => 'ConflictException',
        ], 409);

        expect($error->exceptionMessage)->toBe('Object is checked out')
            ->and($error->exceptionName)->toBe('ConflictException');
    });

    test('backfills the status from the HTTP envelope when the payload omits it', function () {
        $error = errorFor(['Message' => 'something went wrong'], 418);

        expect($error->status)->toBe(418);
    });

    test('survives an Exception key that is not an object', function () {
        $error = errorFor(['Status' => 500, 'Exception' => 'not an object'], 500);

        expect($error->exceptionName)->toBe('')
            ->and($error->status)->toBe(500);
    });
});

describe('MFilesErrorException', function () {
    test('keeps the M-Files message verbatim so existing log matching still works', function () {
        $exception = new MFilesErrorException(errorFor([
            'Status' => 403,
            'Exception' => ['Name' => 'UnauthorizedAccessException', 'Message' => 'Login to application failed'],
        ], 403));

        expect($exception->getMessage())->toBe('Login to application failed')
            ->and($exception->getCode())->toBe(403)
            ->and($exception->status())->toBe(403)
            ->and($exception->isAuthenticationFailure())->toBeTrue();
    });

    test('substitutes a descriptive message when M-Files supplied none', function () {
        $exception = new MFilesErrorException(new MFilesError(
            errorCode: '',
            status: 500,
            url: '/objects/0',
            method: 'POST',
            exceptionName: 'InternalError',
            exceptionMessage: '',
            stack: null,
        ));

        expect($exception->getMessage())->not->toBe('')
            ->and($exception->getMessage())->toContain('500')
            ->and($exception->getMessage())->toContain('InternalError');
    });

    test('exposes request context separately from the message', function () {
        $exception = new MFilesErrorException(errorFor([
            'Status' => 404,
            'URL' => '/objects/0/999/latest',
            'Method' => 'GET',
            'Exception' => ['Name' => 'NotFound', 'Message' => 'Object not found'],
        ], 404));

        expect($exception->context())->toContain('/objects/0/999/latest')
            ->and($exception->context())->toContain('GET')
            ->and($exception->isAuthenticationFailure())->toBeFalse();
    });
});

describe('unexpected success payloads', function () {
    test('a 200 with an HTML body raises MFilesErrorException rather than a TypeError', function () {
        $response = mFilesSend(MockResponse::make('<html>logged out</html>', 200));

        expect(fn () => ObjectPropertiesResponse::createDtoFromResponse($response))
            ->toThrow(MFilesErrorException::class);
    });

    test('a 200 with a JSON scalar raises MFilesErrorException', function () {
        $response = mFilesSend(MockResponse::make('42', 200));

        expect(fn () => ObjectPropertiesResponse::createDtoFromResponse($response))
            ->toThrow(MFilesErrorException::class);
    });

    test('the failure carries the originating request context', function () {
        $response = mFilesSend(
            MockResponse::make('<html>nope</html>', 200),
            new GetObjectInformationRequest(objectType: 0, objectId: 7, objectVersion: 2),
        );

        try {
            ObjectPropertiesResponse::createDtoFromResponse($response);
        } catch (MFilesErrorException $exception) {
            expect($exception->error->method)->toBe('GET')
                ->and($exception->error->url)->toContain('/objects/0/7/2');

            return;
        }

        $this->fail('Expected MFilesErrorException.');
    });
});
