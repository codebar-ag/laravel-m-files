<?php

use CodebarAg\MFiles\Connectors\MFilesConnector;
use CodebarAg\MFiles\DTO\ConfigWithCredentials;
use CodebarAg\MFiles\Requests\GetObjectInformationRequest;
use CodebarAg\MFiles\Requests\LogInToVaultRequest;
use CodebarAg\MFiles\Tests\TestCase;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Laravel\Facades\Saloon;

uses(TestCase::class)
    ->in(__DIR__);

/**
 * A connector configuration suitable for tests.
 *
 * Retries default to a single attempt with no interval so parsing tests do not sleep
 * on the exponential backoff; the retry tests opt in explicitly.
 */
function mFilesConfig(int $tries = 1, int $retryIntervalMilliseconds = 0): ConfigWithCredentials
{
    return new ConfigWithCredentials(
        url: 'https://vault.test',
        vaultGuid: 'vault-guid',
        username: 'tester',
        password: 'secret',
        cacheDriver: 'array',
        tokenTtlSeconds: 3600,
        connectTimeout: 10,
        requestTimeout: 60,
        tries: $tries,
        retryIntervalMilliseconds: $retryIntervalMilliseconds,
    );
}

/**
 * Send a request through a real connector against a faked M-Files response.
 *
 * Exercises the genuine Saloon pipeline — headers, plugins, retries — rather than
 * hand-building a Response, so the tests cover how the package actually behaves.
 *
 * @param  MockResponse|list<MockResponse>  $mock  A single response, or a sequence
 *                                                 returned one per attempt.
 */
function mFilesSend(
    MockResponse|array $mock,
    ?Request $request = null,
    int $tries = 1,
    ?MFilesConnector $connector = null,
): Response {
    $request ??= new GetObjectInformationRequest(objectType: 0, objectId: 1, objectVersion: 1);

    $responses = is_array($mock) ? array_values($mock) : [$mock];

    // Saloon has no array-sequence support under a class key — MockClient::addResponse()
    // only accepts MockResponse|Fixture|callable — so a multi-response sequence is
    // expressed as a callable that walks the list, repeating the last entry once the
    // attempts outrun it.
    $sequence = count($responses) === 1
        ? $responses[0]
        : (function () use ($responses): MockResponse {
            static $index = 0;

            return $responses[min($index++, count($responses) - 1)];
        });

    Saloon::fake([
        LogInToVaultRequest::class => MockResponse::make(['Value' => 'fake-token'], 200),
        $request::class => $sequence,
    ]);

    return ($connector ?? new MFilesConnector(mFilesConfig($tries)))->send($request);
}
