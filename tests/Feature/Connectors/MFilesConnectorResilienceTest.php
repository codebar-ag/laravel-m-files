<?php

declare(strict_types=1);

use CodebarAg\MFiles\Connectors\MFilesConnector;
use CodebarAg\MFiles\DTO\ConfigWithCredentials;
use CodebarAg\MFiles\DTO\SetProperty;
use CodebarAg\MFiles\Enums\MFDataTypeEnum;
use CodebarAg\MFiles\Exceptions\MFilesErrorException;
use CodebarAg\MFiles\Helpers\CacheKeyManager;
use CodebarAg\MFiles\Requests\CreateSingleFileDocumentRequest;
use CodebarAg\MFiles\Requests\GetObjectInformationRequest;
use CodebarAg\MFiles\Requests\LogInToVaultRequest;
use CodebarAg\MFiles\Requests\SetPropertiesRequest;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\RequestOptions;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Laravel\Facades\Saloon;

/**
 * An M-Files style error payload for the given status.
 */
function resilienceError(int $status, string $message = 'boom'): MockResponse
{
    return MockResponse::make([
        'Status' => $status,
        'Message' => $message,
    ], $status);
}

/**
 * A minimal successful payload. The retry tests care about how many attempts left the
 * connector, not about DTO parsing, so the body is deliberately trivial.
 */
function resilienceOk(): MockResponse
{
    return MockResponse::make(['ObjectVersion' => ['ObjVer' => ['Type' => 0, 'ID' => 1, 'Version' => 1]]], 200);
}

function resilienceGet(): GetObjectInformationRequest
{
    return new GetObjectInformationRequest(objectType: 0, objectId: 1, objectVersion: 1);
}

function resiliencePost(): CreateSingleFileDocumentRequest
{
    return new CreateSingleFileDocumentRequest(title: 'Invoice', files: [['UploadID' => 1]]);
}

/**
 * Send a request through a real connector, answering each attempt with the next mock
 * in the sequence and repeating the last one once the sequence runs dry.
 *
 * mFilesSend()'s list form cannot be used here: handing Saloon::fake() an array under
 * a request-class key hits MockClient::addResponse()'s MockResponse|Fixture|callable
 * signature and raises a TypeError, so the sequence is expressed as a callable instead.
 *
 * @param  list<MockResponse>  $responses
 */
function resilienceSend(
    array $responses,
    Request $request,
    int $tries = 1,
    ?MFilesConnector $connector = null,
): Response {
    $queue = $responses;

    Saloon::fake([
        LogInToVaultRequest::class => MockResponse::make(['Value' => 'fake-token'], 200),
        $request::class => static function () use (&$queue, $responses): MockResponse {
            return array_shift($queue) ?? $responses[array_key_last($responses)];
        },
    ]);

    return ($connector ?? new MFilesConnector(mFilesConfig($tries)))->send($request);
}

/**
 * How many times a specific request class actually left the connector.
 */
function resilienceSentCount(string $requestClass): int
{
    $responses = MockClient::getGlobal()?->getRecordedResponses() ?? [];

    return count(array_filter(
        $responses,
        static fn (Response $response): bool => $response->getRequest()::class === $requestClass,
    ));
}

describe('timeouts', function () {
    test('the HasTimeout plugin puts the configured timeouts into the Guzzle config', function () {
        // Guzzle defaults both of these to "wait forever". Without them a single
        // unresponsive vault can hold a PHP worker until the process is killed, so the
        // values have to survive all the way into the options the sender receives.
        $config = new ConfigWithCredentials(
            url: 'https://vault.test',
            vaultGuid: 'vault-guid',
            username: 'tester',
            password: 'secret',
            cacheDriver: 'array',
            tokenTtlSeconds: 3600,
            connectTimeout: 3,
            requestTimeout: 17,
        );

        // Building the pending request boots the plugins, resolves the token (so the
        // login call has to be faked) and resolves the mock for the request itself.
        Saloon::fake([
            LogInToVaultRequest::class => MockResponse::make(['Value' => 'fake-token'], 200),
            GetObjectInformationRequest::class => resilienceOk(),
        ]);

        $connector = new MFilesConnector($config);

        $guzzleConfig = $connector->createPendingRequest(resilienceGet())->config()->all();

        expect($guzzleConfig[RequestOptions::CONNECT_TIMEOUT])->toEqual(3)
            ->and($guzzleConfig[RequestOptions::TIMEOUT])->toEqual(17);
    })->group('connectors');

    test('the connector mirrors the configured timeouts onto the plugin properties', function () {
        $connector = new MFilesConnector(mFilesConfig());

        expect($connector->connectTimeout)->toBe(10)
            ->and($connector->requestTimeout)->toBe(60)
            ->and($connector->getConnectTimeout())->toEqual(10.0)
            ->and($connector->getRequestTimeout())->toEqual(60.0);
    })->group('connectors');

    test('a config with different timeouts produces a different Guzzle config', function () {
        // Guards against the values being hard-coded on the connector rather than read
        // from the configuration object.
        Saloon::fake([
            LogInToVaultRequest::class => MockResponse::make(['Value' => 'fake-token'], 200),
            GetObjectInformationRequest::class => resilienceOk(),
        ]);

        $config = new ConfigWithCredentials(
            url: 'https://vault.test',
            vaultGuid: 'vault-guid',
            username: 'tester',
            password: 'secret',
            cacheDriver: 'array',
            tokenTtlSeconds: 3600,
            connectTimeout: 1,
            requestTimeout: 2,
        );

        $guzzleConfig = (new MFilesConnector($config))
            ->createPendingRequest(resilienceGet())
            ->config()
            ->all();

        expect($guzzleConfig[RequestOptions::CONNECT_TIMEOUT])->toEqual(1)
            ->and($guzzleConfig[RequestOptions::TIMEOUT])->toEqual(2);
    })->group('connectors');
});

describe('stale token recovery', function () {
    test('a 401 forgets the cached token and the request is retried', function () {
        // The scenario this exists for: the vault restarted (or expired the session
        // early) while our token was still inside its TTL. Nothing invalidated our
        // copy, so every request 401'd until the TTL lapsed — minutes of hard downtime
        // for a token the vault had already forgotten.
        $config = mFilesConfig(tries: 2);
        $cacheKeyManager = new CacheKeyManager($config);
        $cacheKeyManager->setAuthToken('stale-token', 3600);

        $connector = new MFilesConnector($config, $cacheKeyManager);

        // Seeded, so nothing would re-authenticate on its own — only handleRetry() can
        // drop this token.
        expect($cacheKeyManager->hasAuthToken())->toBeTrue();

        $response = resilienceSend(
            [resilienceError(401, 'Session expired'), resilienceOk()],
            resilienceGet(),
            connector: $connector,
        );

        expect($response->status())->toBe(200)
            // The dead token no longer poisons the cache for the rest of its TTL — it
            // has been replaced by the one the replay's re-authentication issued.
            ->and($cacheKeyManager->getAuthToken())->toBe('fake-token')
            // Two object requests: the 401 and its replay.
            ->and(resilienceSentCount(GetObjectInformationRequest::class))->toBe(2);
    })->group('connectors');

    test('the invalidation re-authenticates within the same send and caches the new token', function () {
        $config = mFilesConfig(tries: 2);
        $cacheKeyManager = new CacheKeyManager($config);
        $cacheKeyManager->setAuthToken('stale-token', 3600);

        resilienceSend(
            [resilienceError(401), resilienceOk()],
            resilienceGet(),
            connector: new MFilesConnector($config, $cacheKeyManager),
        );

        // The seeded token meant no login was needed for the first attempt, so this
        // login can only come from the 401 invalidation — recovery happens inside the
        // failing send, not merely on some later one.
        expect(resilienceSentCount(LogInToVaultRequest::class))->toBe(1);

        // A subsequent connector reuses the refreshed token rather than logging in again.
        $second = (new MFilesConnector($config, $cacheKeyManager))->send(resilienceGet());

        expect(resilienceSentCount(LogInToVaultRequest::class))->toBe(1)
            ->and($second->getPendingRequest()->headers()->get('X-Authentication'))->toBe('fake-token')
            ->and($cacheKeyManager->getAuthToken())->toBe('fake-token');
    })->group('connectors');

    test('the replayed attempt carries the freshly issued token, not the rejected one', function () {
        // Saloon's HasHeaders trait memoises the connector's header store
        // (`$this->headers ??= new ArrayStore($this->defaultHeaders())`), so
        // MFilesConnector::defaultHeaders() — and with it getToken() — would otherwise
        // run exactly once per connector instance and the replay would reuse the very
        // token that produced the 401. forgetToken() unsets that memoised store, which
        // is what makes recovery effective inside the same send.
        $config = mFilesConfig(tries: 2);
        $cacheKeyManager = new CacheKeyManager($config);
        $cacheKeyManager->setAuthToken('stale-token', 3600);

        resilienceSend(
            [resilienceError(401), resilienceOk()],
            resilienceGet(),
            connector: new MFilesConnector($config, $cacheKeyManager),
        );

        $tokens = collect(MockClient::getGlobal()?->getRecordedResponses() ?? [])
            ->filter(fn (Response $response): bool => $response->getRequest() instanceof GetObjectInformationRequest)
            ->map(fn (Response $response): mixed => $response->getPendingRequest()->headers()->get('X-Authentication'))
            ->values()
            ->all();

        expect($tokens)->toBe(['stale-token', 'fake-token']);
    })->group('connectors');

    test('a 401 that never recovers still surfaces MFilesErrorException', function () {
        $config = mFilesConfig(tries: 2);
        $cacheKeyManager = new CacheKeyManager($config);
        $cacheKeyManager->setAuthToken('stale-token', 3600);

        $response = resilienceSend(
            [resilienceError(401), resilienceError(401)],
            resilienceGet(),
            connector: new MFilesConnector($config, $cacheKeyManager),
        );

        expect($response->status())->toBe(401)
            ->and(resilienceSentCount(GetObjectInformationRequest::class))->toBe(2)
            ->and(fn () => $response->dto())->toThrow(MFilesErrorException::class);
    })->group('connectors');
});

describe('statuses that must not be replayed', function () {
    test('a 403 is not retried because it is a permission failure, not a stale session', function () {
        // Re-authenticating would not help: the credentials are valid, the user simply
        // may not touch this object. Retrying only multiplies the audit-log noise.
        $response = resilienceSend(
            [resilienceError(403, 'Access denied'), resilienceOk()],
            resilienceGet(),
            tries: 2,
        );

        expect($response->status())->toBe(403)
            ->and(resilienceSentCount(GetObjectInformationRequest::class))->toBe(1);
    })->group('connectors');

    test('the unretried 403 still flows through the response classes to MFilesErrorException', function () {
        $response = resilienceSend(
            [resilienceError(403, 'Access denied'), resilienceOk()],
            resilienceGet(),
            tries: 2,
        );

        // Not Saloon's RequestException: throwOnMaxTries=false hands the response back
        // so the package's own error mapping produces the exception callers catch.
        expect($response)->toBeInstanceOf(Response::class);

        $caught = null;

        try {
            $response->dto();
        } catch (MFilesErrorException $exception) {
            $caught = $exception;
        }

        expect($caught)->toBeInstanceOf(MFilesErrorException::class)
            ->and($caught?->status())->toBe(403)
            ->and($caught?->isAuthenticationFailure())->toBeTrue();
    })->group('connectors');

    test('a 404 is not retried either', function () {
        $response = resilienceSend(
            [resilienceError(404, 'Not found'), resilienceOk()],
            resilienceGet(),
            tries: 2,
        );

        expect($response->status())->toBe(404)
            ->and(resilienceSentCount(GetObjectInformationRequest::class))->toBe(1);
    })->group('connectors');
});

describe('statuses that are always safe to replay', function () {
    test('a 503 is retried', function () {
        // The vault was not ready to process anything, so nothing was processed.
        $response = resilienceSend(
            [resilienceError(503, 'Service unavailable'), resilienceOk()],
            resilienceGet(),
            tries: 2,
        );

        expect($response->status())->toBe(200)
            ->and(resilienceSentCount(GetObjectInformationRequest::class))->toBe(2);
    })->group('connectors');

    test('a 429 is retried', function () {
        $response = resilienceSend(
            [resilienceError(429, 'Too many requests'), resilienceOk()],
            resilienceGet(),
            tries: 2,
        );

        expect($response->status())->toBe(200)
            ->and(resilienceSentCount(GetObjectInformationRequest::class))->toBe(2);
    })->group('connectors');

    test('a 503 is retried even for a document-creating POST', function () {
        // 503 means the request was rejected before it reached the vault logic, so the
        // replay cannot produce a second document.
        $response = resilienceSend(
            [resilienceError(503), resilienceOk()],
            resiliencePost(),
            tries: 2,
        );

        expect($response->status())->toBe(200)
            ->and(resilienceSentCount(CreateSingleFileDocumentRequest::class))->toBe(2);
    })->group('connectors');
});

describe('ambiguous server faults', function () {
    test('a 500 is retried for an idempotent GET', function () {
        $response = resilienceSend(
            [resilienceError(500, 'Internal server error'), resilienceOk()],
            resilienceGet(),
            tries: 2,
        );

        expect($response->status())->toBe(200)
            ->and(resilienceSentCount(GetObjectInformationRequest::class))->toBe(2);
    })->group('connectors');

    test('a 502 is retried for an idempotent GET', function () {
        $response = resilienceSend(
            [resilienceError(502), resilienceOk()],
            resilienceGet(),
            tries: 2,
        );

        expect($response->status())->toBe(200)
            ->and(resilienceSentCount(GetObjectInformationRequest::class))->toBe(2);
    })->group('connectors');

    test('a 504 is retried for an idempotent GET', function () {
        $response = resilienceSend(
            [resilienceError(504), resilienceOk()],
            resilienceGet(),
            tries: 2,
        );

        expect($response->status())->toBe(200)
            ->and(resilienceSentCount(GetObjectInformationRequest::class))->toBe(2);
    })->group('connectors');

    test('a 500 is NOT retried for a document-creating POST', function () {
        // The vault may well have committed the document before the fault. Replaying
        // would hand the user two identical documents, which is far worse than an
        // error they can act on.
        $response = resilienceSend(
            [resilienceError(500), resilienceOk()],
            resiliencePost(),
            tries: 2,
        );

        expect($response->status())->toBe(500)
            ->and(resilienceSentCount(CreateSingleFileDocumentRequest::class))->toBe(1);
    })->group('connectors');

    test('a 502 is NOT retried for a property-writing POST', function () {
        $request = new SetPropertiesRequest(
            objectType: 0,
            objectId: 1,
            objectVersion: 1,
            propertyValues: [new SetProperty(0, MFDataTypeEnum::TEXT, 'Title')],
        );

        $response = resilienceSend(
            [resilienceError(502), resilienceOk()],
            $request,
            tries: 2,
        );

        expect($response->status())->toBe(502)
            ->and(resilienceSentCount(SetPropertiesRequest::class))->toBe(1);
    })->group('connectors');

    test('a 504 is NOT retried for a document-creating POST', function () {
        $response = resilienceSend(
            [resilienceError(504), resilienceOk()],
            resiliencePost(),
            tries: 2,
        );

        expect($response->status())->toBe(504)
            ->and(resilienceSentCount(CreateSingleFileDocumentRequest::class))->toBe(1);
    })->group('connectors');
});

describe('exhausting the attempts', function () {
    test('the last response is returned instead of Saloon throwing RequestException', function () {
        // throwOnMaxTries=false is what preserves this. With Saloon's default the final
        // failure would escape as RequestException and every caller catching
        // MFilesErrorException would miss it.
        $response = resilienceSend(
            [resilienceError(503), resilienceError(503), resilienceError(503)],
            resilienceGet(),
            tries: 3,
        );

        expect($response)->toBeInstanceOf(Response::class)
            ->and($response->status())->toBe(503)
            ->and(resilienceSentCount(GetObjectInformationRequest::class))->toBe(3);
    })->group('connectors');

    test('the exhausted failure still becomes MFilesErrorException, not RequestException', function () {
        $response = resilienceSend(
            [resilienceError(503, 'Vault is down'), resilienceError(503, 'Vault is down')],
            resilienceGet(),
            tries: 2,
        );

        expect(fn () => $response->dto())
            ->toThrow(MFilesErrorException::class)
            ->and(fn () => $response->dto())
            ->not->toThrow(RequestException::class);

        $caught = null;

        try {
            $response->dto();
        } catch (MFilesErrorException $exception) {
            $caught = $exception;
        }

        expect($caught)->toBeInstanceOf(MFilesErrorException::class)
            ->and($caught?->status())->toBe(503)
            ->and($caught?->getMessage())->toContain('Vault is down');
    })->group('connectors');

    test('sending with tries=1 disables retrying altogether', function () {
        $response = mFilesSend(
            resilienceError(503),
            resilienceGet(),
            tries: 1,
        );

        expect($response->status())->toBe(503)
            ->and(resilienceSentCount(GetObjectInformationRequest::class))->toBe(1)
            ->and(fn () => $response->dto())->toThrow(MFilesErrorException::class);
    })->group('connectors');
});

describe('connector retry settings', function () {
    test('the connector reads tries and the retry interval from the configuration', function () {
        $connector = new MFilesConnector(mFilesConfig(tries: 4, retryIntervalMilliseconds: 250));

        expect($connector->tries)->toBe(4)
            ->and($connector->retryInterval)->toBe(250)
            ->and($connector->useExponentialBackoff)->toBeTrue()
            // Keeps the failure inside the package's own exception type.
            ->and($connector->throwOnMaxTries)->toBeFalse();
    })->group('connectors');

    test('handleRetry replays a connection-level failure regardless of the HTTP method', function () {
        // A FatalRequestException means the request never reached the application, so
        // even a POST cannot have been committed.
        Saloon::fake([
            LogInToVaultRequest::class => MockResponse::make(['Value' => 'fake-token'], 200),
            CreateSingleFileDocumentRequest::class => resilienceOk(),
        ]);

        $connector = new MFilesConnector(mFilesConfig(tries: 2));

        $exception = new FatalRequestException(
            new ConnectException('Connection refused', new GuzzleRequest('POST', 'https://vault.test')),
            $connector->createPendingRequest(resiliencePost()),
        );

        expect($connector->handleRetry($exception, resiliencePost()))->toBeTrue();
    })->group('connectors');
});
