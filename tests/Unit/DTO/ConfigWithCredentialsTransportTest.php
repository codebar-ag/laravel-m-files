<?php

declare(strict_types=1);

use CodebarAg\MFiles\DTO\ConfigWithCredentials;

/**
 * A directly constructed config, bypassing fromArray() and the Laravel config.
 */
function transportDto(
    int $connectTimeout = ConfigWithCredentials::DEFAULT_CONNECT_TIMEOUT,
    int $requestTimeout = ConfigWithCredentials::DEFAULT_REQUEST_TIMEOUT,
    int $tries = ConfigWithCredentials::DEFAULT_TRIES,
    int $retryIntervalMilliseconds = ConfigWithCredentials::DEFAULT_RETRY_INTERVAL_MILLISECONDS,
): ConfigWithCredentials {
    return new ConfigWithCredentials(
        url: 'https://example.test',
        vaultGuid: 'vault-1',
        username: 'alice',
        password: 'secret',
        cacheDriver: 'array',
        tokenTtlSeconds: 3600,
        connectTimeout: $connectTimeout,
        requestTimeout: $requestTimeout,
        tries: $tries,
        retryIntervalMilliseconds: $retryIntervalMilliseconds,
    );
}

/**
 * A config built through fromArray(), so the Laravel config supplies the defaults.
 *
 * @param  array<string, mixed>  $overrides
 */
function transportConfig(array $overrides = []): ConfigWithCredentials
{
    return ConfigWithCredentials::fromArray([
        'url' => 'https://example.test',
        'vaultGuid' => 'vault-1',
        'username' => 'alice',
        'password' => 'secret',
        ...$overrides,
    ]);
}

describe('transport defaults', function () {
    test('the constructor falls back to the DEFAULT_* constants', function () {
        $dto = new ConfigWithCredentials(
            url: 'https://example.test',
            vaultGuid: 'vault-1',
            username: 'alice',
            password: 'secret',
        );

        expect($dto->connectTimeout)->toBe(ConfigWithCredentials::DEFAULT_CONNECT_TIMEOUT)
            ->and($dto->requestTimeout)->toBe(ConfigWithCredentials::DEFAULT_REQUEST_TIMEOUT)
            ->and($dto->tries)->toBe(ConfigWithCredentials::DEFAULT_TRIES)
            ->and($dto->retryIntervalMilliseconds)->toBe(ConfigWithCredentials::DEFAULT_RETRY_INTERVAL_MILLISECONDS);
    })->group('dto');

    test('the defaults are the values the package documents', function () {
        // Pinned deliberately: these are the numbers README and config/m-files.php
        // quote, and a silent change to them changes every consumer's timeout budget.
        expect(ConfigWithCredentials::DEFAULT_CONNECT_TIMEOUT)->toBe(10)
            ->and(ConfigWithCredentials::DEFAULT_REQUEST_TIMEOUT)->toBe(60)
            ->and(ConfigWithCredentials::DEFAULT_TRIES)->toBe(3)
            ->and(ConfigWithCredentials::DEFAULT_RETRY_INTERVAL_MILLISECONDS)->toBe(500);
    })->group('dto');

    test('the transport parameters are appended so positional construction still works', function () {
        // The DTO is constructed positionally in the wild; inserting the new arguments
        // anywhere but at the end would silently shift password into cacheDriver.
        $dto = new ConfigWithCredentials(
            'https://example.test',
            'vault-1',
            'alice',
            'secret',
            'array',
            900,
        );

        expect($dto->password)->toBe('secret')
            ->and($dto->cacheDriver)->toBe('array')
            ->and($dto->tokenTtlSeconds)->toBe(900)
            ->and($dto->tries)->toBe(ConfigWithCredentials::DEFAULT_TRIES);
    })->group('dto');
});

describe('constructor clamping', function () {
    test('tries below 1 is clamped to 1', function () {
        // 0 tries would mean "never send the request at all"; Saloon would also treat
        // it as 1, so the DTO normalises rather than leaving the two disagreeing.
        expect(transportDto(tries: 0)->tries)->toBe(1)
            ->and(transportDto(tries: -5)->tries)->toBe(1)
            ->and(transportDto(tries: 1)->tries)->toBe(1);
    })->group('dto');

    test('a negative connect timeout becomes 0, and 0 is kept', function () {
        // 0 is Guzzle's "no limit". It is a legitimate, if unwise, choice, so the
        // constructor allows it and only refuses nonsense negatives.
        expect(transportDto(connectTimeout: -1)->connectTimeout)->toBe(0)
            ->and(transportDto(connectTimeout: 0)->connectTimeout)->toBe(0)
            ->and(transportDto(connectTimeout: 4)->connectTimeout)->toBe(4);
    })->group('dto');

    test('a negative request timeout becomes 0, and 0 is kept', function () {
        expect(transportDto(requestTimeout: -30)->requestTimeout)->toBe(0)
            ->and(transportDto(requestTimeout: 0)->requestTimeout)->toBe(0)
            ->and(transportDto(requestTimeout: 90)->requestTimeout)->toBe(90);
    })->group('dto');

    test('a negative retry interval becomes 0, and 0 is kept', function () {
        // 0 means "retry immediately", which is exactly what the test suite wants, so
        // it must survive the constructor untouched.
        expect(transportDto(retryIntervalMilliseconds: -1)->retryIntervalMilliseconds)->toBe(0)
            ->and(transportDto(retryIntervalMilliseconds: 0)->retryIntervalMilliseconds)->toBe(0)
            ->and(transportDto(retryIntervalMilliseconds: 250)->retryIntervalMilliseconds)->toBe(250);
    })->group('dto');
});

describe('fromArray and the Laravel config', function () {
    beforeEach(function () {
        config([
            'm-files.cache_driver' => 'array',
            'm-files.http.connect_timeout' => 5,
            'm-files.http.timeout' => 11,
            'm-files.http.tries' => 4,
            'm-files.http.retry_interval' => 250,
        ]);
    });

    test('the transport settings are read from the m-files.http config keys', function () {
        $dto = transportConfig();

        expect($dto->connectTimeout)->toBe(5)
            ->and($dto->requestTimeout)->toBe(11)
            ->and($dto->tries)->toBe(4)
            ->and($dto->retryIntervalMilliseconds)->toBe(250);
    })->group('dto');

    test('explicit array values win over the config', function () {
        $dto = transportConfig([
            'connectTimeout' => 2,
            'requestTimeout' => 3,
            'tries' => 7,
            'retryIntervalMilliseconds' => 40,
        ]);

        expect($dto->connectTimeout)->toBe(2)
            ->and($dto->requestTimeout)->toBe(3)
            ->and($dto->tries)->toBe(7)
            ->and($dto->retryIntervalMilliseconds)->toBe(40);
    })->group('dto');

    test('the DEFAULT_* constants apply when the config section is absent', function () {
        // A host application that published an older config file has no "http" section
        // at all, so the defaults must come from the DTO rather than from null.
        config(['m-files.http' => null]);

        $dto = transportConfig();

        expect($dto->connectTimeout)->toBe(ConfigWithCredentials::DEFAULT_CONNECT_TIMEOUT)
            ->and($dto->requestTimeout)->toBe(ConfigWithCredentials::DEFAULT_REQUEST_TIMEOUT)
            ->and($dto->tries)->toBe(ConfigWithCredentials::DEFAULT_TRIES)
            ->and($dto->retryIntervalMilliseconds)->toBe(ConfigWithCredentials::DEFAULT_RETRY_INTERVAL_MILLISECONDS);
    })->group('dto');

    test('fromArray accepts numeric strings, which is what env() hands over', function () {
        $dto = transportConfig([
            'connectTimeout' => '8',
            'tries' => '2',
        ]);

        expect($dto->connectTimeout)->toBe(8)
            ->and($dto->tries)->toBe(2);
    })->group('dto');

    test('fromArray rejects a non-integer transport value', function () {
        transportConfig(['tries' => 'many']);
    })->throws(InvalidArgumentException::class, 'Config [tries] must be a positive integer.')->group('dto');

    test('fromArray floors the transport values at 1, not at 0 like the constructor', function () {
        // optionalPositiveInt() applies max(1, …) to every field, so fromArray cannot
        // express the "0 = no limit / no wait" values the constructor accepts. Worth
        // knowing before assuming M_FILES_RETRY_INTERVAL_MS=0 disables the backoff.
        $dto = transportConfig([
            'connectTimeout' => 0,
            'requestTimeout' => 0,
            'tries' => 0,
            'retryIntervalMilliseconds' => 0,
        ]);

        expect($dto->connectTimeout)->toBe(1)
            ->and($dto->requestTimeout)->toBe(1)
            ->and($dto->tries)->toBe(1)
            ->and($dto->retryIntervalMilliseconds)->toBe(1);
    })->group('dto');
});

describe('toArray and round-tripping', function () {
    test('toArray carries the transport settings', function () {
        $dto = transportDto(connectTimeout: 4, requestTimeout: 44, tries: 5, retryIntervalMilliseconds: 60);

        expect($dto->toArray())->toBe([
            'url' => 'https://example.test',
            'vaultGuid' => 'vault-1',
            'username' => 'alice',
            'password' => 'secret',
            'cacheDriver' => 'array',
            'tokenTtlSeconds' => 3600,
            'connectTimeout' => 4,
            'requestTimeout' => 44,
            'tries' => 5,
            'retryIntervalMilliseconds' => 60,
        ]);
    })->group('dto');

    test('fromArray(toArray()) reproduces an equal object', function () {
        // Anything that serialises the config — a queued job payload, a cache entry —
        // relies on this, and a transport key missing from toArray() would silently
        // reset that side of the round trip to the defaults.
        $dto = transportDto(connectTimeout: 7, requestTimeout: 21, tries: 2, retryIntervalMilliseconds: 125);

        $roundTripped = ConfigWithCredentials::fromArray($dto->toArray());

        expect($roundTripped)->toEqual($dto)
            ->and($roundTripped->toArray())->toBe($dto->toArray());
    })->group('dto');

    test('a null cache driver survives the round trip', function () {
        $dto = new ConfigWithCredentials(
            url: 'https://example.test',
            vaultGuid: 'vault-1',
            username: 'alice',
            password: 'secret',
            cacheDriver: null,
        );

        expect(ConfigWithCredentials::fromArray($dto->toArray()))->toEqual($dto);
    })->group('dto');
});

describe('password redaction', function () {
    test('__debugInfo replaces the password but keeps every other key', function () {
        $dto = transportDto();

        $debug = $dto->__debugInfo();

        expect($debug['password'])->toBe('********')
            ->and($debug['url'])->toBe('https://example.test')
            ->and($debug['username'])->toBe('alice')
            ->and($debug['tries'])->toBe($dto->tries)
            ->and(array_keys($debug))->toBe(array_keys($dto->toArray()));
    })->group('dto');

    test('print_r does not leak the vault credential', function () {
        // The realistic leak: an exception renderer or a stray dd() in a controller
        // dumps the config object straight into an HTML error page or a log file.
        $dto = transportDto();

        expect(print_r($dto, true))->not->toContain('secret')
            ->and(print_r($dto, true))->toContain('********');
    })->group('dto');

    test('var_dump does not leak the vault credential', function () {
        $dto = transportDto();

        ob_start();
        var_dump($dto);
        $dumped = (string) ob_get_clean();

        expect($dumped)->not->toContain('secret')
            ->and($dumped)->toContain('********');
    })->group('dto');

    test('exporting the debug info does not leak the vault credential', function () {
        $dto = transportDto();

        expect(var_export($dto->__debugInfo(), true))->not->toContain('secret');
    })->group('dto');

    test('toArray still returns the real password so the round trip keeps working', function () {
        // Redacting here instead would produce a config that authenticates with
        // "********" the moment it came back out of a cache or a queue payload.
        $dto = transportDto();

        expect($dto->toArray()['password'])->toBe('secret')
            ->and($dto->password)->toBe('secret')
            ->and(ConfigWithCredentials::fromArray($dto->toArray())->password)->toBe('secret');
    })->group('dto');
});
