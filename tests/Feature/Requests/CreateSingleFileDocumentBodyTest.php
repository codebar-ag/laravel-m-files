<?php

declare(strict_types=1);

use CodebarAg\MFiles\Connectors\MFilesConnector;
use CodebarAg\MFiles\DTO\SetProperty;
use CodebarAg\MFiles\Enums\MFDataTypeEnum;
use CodebarAg\MFiles\Requests\CreateSingleFileDocumentRequest;
use CodebarAg\MFiles\Requests\LogInToVaultRequest;
use Psr\Http\Message\RequestInterface;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;

/**
 * Regression cover for the document-creation body.
 *
 * The body used to wrap `$files` unconditionally: `'Files' => [$this->files]`. That
 * only produced valid JSON when a *single* associative upload-info array was passed;
 * the shape the README documents — `files: [$uploadedFile]` — became a nested
 * `[[{…}]]` that M-Files rejects, which also made multi-file documents impossible.
 * The request now normalises via `array_is_list()`, so both shapes serialise to the
 * flat list M-Files expects.
 */
beforeEach(function () {
    // Building a PendingRequest runs Saloon's mock-resolution middleware, so every
    // request the connector may construct needs a faked entry even though these tests
    // only ever inspect the outgoing PSR request.
    Saloon::fake([
        LogInToVaultRequest::class => MockResponse::make(['Value' => 'fake-token'], 200),
        CreateSingleFileDocumentRequest::class => MockResponse::make([], 200),
    ]);
});

/**
 * Build the request exactly as the connector would put it on the wire.
 *
 * @param  array<string, mixed>|list<mixed>  $files
 * @param  list<SetProperty>  $propertyValues
 */
function createDocumentPsrRequest(array $files = [], array $propertyValues = []): RequestInterface
{
    return (new MFilesConnector(mFilesConfig()))
        ->createPendingRequest(new CreateSingleFileDocumentRequest(
            title: 'My Document',
            files: $files,
            propertyValues: $propertyValues,
        ))
        ->createPsrRequest();
}

/**
 * The decoded JSON body the vault would actually receive.
 *
 * @param  array<string, mixed>|list<mixed>  $files
 * @param  list<SetProperty>  $propertyValues
 * @return array<string, mixed>
 */
function createDocumentBody(array $files = [], array $propertyValues = []): array
{
    /** @var array<string, mixed> $decoded */
    $decoded = json_decode((string) createDocumentPsrRequest($files, $propertyValues)->getBody(), true, 512, JSON_THROW_ON_ERROR);

    return $decoded;
}

describe('Files normalisation', function () {
    test('a single upload-info array becomes a one-element list', function () {
        // The historical call style. It must stay working, and must not gain a second
        // level of nesting now that lists are also accepted.
        $file = ['UploadID' => 1, 'Title' => 'x', 'Extension' => 'pdf'];

        $body = createDocumentBody(files: $file);

        expect($body['Files'])->toBe([$file])
            ->and($body['Files'])->toHaveCount(1)
            ->and(array_is_list($body['Files']))->toBeTrue()
            // The bug signature: the upload info sitting one level too deep.
            ->and($body['Files'][0])->toHaveKey('UploadID')
            ->and(array_is_list($body['Files'][0]))->toBeFalse();
    });

    test('a list of upload-info arrays keeps every element', function () {
        // The README form, `files: [$uploadedFile]`, generalised to two files. This is
        // the case that used to serialise as [[{…},{…}]] and be rejected by M-Files.
        $fileA = ['UploadID' => 1, 'Title' => 'invoice', 'Extension' => 'pdf'];
        $fileB = ['UploadID' => 2, 'Title' => 'attachment', 'Extension' => 'docx'];

        $body = createDocumentBody(files: [$fileA, $fileB]);

        expect($body['Files'])->toBe([$fileA, $fileB])
            ->and($body['Files'])->toHaveCount(2)
            ->and(array_is_list($body['Files']))->toBeTrue()
            ->and($body['Files'][0]['UploadID'])->toBe(1)
            ->and($body['Files'][1]['UploadID'])->toBe(2);
    });

    test('the single documented file wraps to exactly one element', function () {
        // Literally the README snippet: one uploaded file passed inside a list.
        $uploadedFile = ['UploadID' => 7, 'Title' => 'document', 'Extension' => 'pdf'];

        $body = createDocumentBody(files: [$uploadedFile]);

        expect($body['Files'])->toBe([$uploadedFile]);
    });

    test('no files produce an empty list rather than a list holding an empty array', function () {
        // `[[]]` is not "no files" to M-Files — it is one malformed file entry, which
        // is what the unconditional wrap used to send.
        $body = createDocumentBody(files: []);

        expect($body['Files'])->toBe([])
            ->and($body['Files'])->not->toBe([[]]);

        // And it has to be encoded as a JSON array, not an object.
        expect((string) createDocumentPsrRequest()->getBody())->toContain('"Files":[]');
    });

    test('non-array entries in the list are filtered out', function () {
        // Defensive: a caller mapping over upload responses can easily leak a null or
        // a scalar in, and a malformed entry would make the vault reject the whole
        // document rather than just that file.
        $file = ['UploadID' => 3, 'Title' => 'report', 'Extension' => 'pdf'];

        $body = createDocumentBody(files: [$file, null, 'not-a-file', 42]);

        expect($body['Files'])->toBe([$file])
            // Filtering must reindex, otherwise the gaps turn the JSON array into an object.
            ->and(array_is_list($body['Files']))->toBeTrue();
    });
});

describe('PropertyValues serialisation', function () {
    test('SetProperty objects serialise to their M-Files shape', function () {
        $body = createDocumentBody(
            files: ['UploadID' => 1, 'Title' => 'x', 'Extension' => 'pdf'],
            propertyValues: [
                new SetProperty(propertyDef: 0, dataType: MFDataTypeEnum::TEXT, value: 'Custom Title'),
                new SetProperty(propertyDef: 100, dataType: MFDataTypeEnum::LOOKUP, value: 42),
            ],
        );

        expect($body['PropertyValues'])->toBe([
            [
                'PropertyDef' => 0,
                'TypedValue' => [
                    'DataType' => MFDataTypeEnum::TEXT->value,
                    'Value' => 'Custom Title',
                ],
            ],
            [
                'PropertyDef' => 100,
                'TypedValue' => [
                    'DataType' => 9,
                    'Lookup' => [
                        'Item' => 42,
                        'Version' => -1,
                    ],
                ],
            ],
        ]);
    });

    test('PropertyValues encodes as a JSON array in caller order', function () {
        // A non-sequentially-keyed array would encode as a JSON object, which M-Files
        // rejects — so the reindexing has to survive all the way to the wire.
        $properties = [
            new SetProperty(propertyDef: 0, dataType: MFDataTypeEnum::TEXT, value: 'A'),
            new SetProperty(propertyDef: 100, dataType: MFDataTypeEnum::LOOKUP, value: 1),
            new SetProperty(propertyDef: 5, dataType: MFDataTypeEnum::TEXT, value: 'B'),
        ];

        $body = createDocumentBody(propertyValues: $properties);

        expect(array_is_list($body['PropertyValues']))->toBeTrue()
            ->and($body['PropertyValues'])->toHaveCount(3)
            ->and(array_column($body['PropertyValues'], 'PropertyDef'))->toBe([0, 100, 5]);

        expect((string) createDocumentPsrRequest([], $properties)->getBody())
            ->not->toContain('"PropertyValues":{')
            ->toContain('"PropertyValues":[');
    });

    test('no property values produce an empty list', function () {
        $body = createDocumentBody(files: ['UploadID' => 1]);

        expect($body['PropertyValues'])->toBe([]);

        expect((string) createDocumentPsrRequest(['UploadID' => 1])->getBody())->toContain('"PropertyValues":[]');
    });
});

describe('transport', function () {
    test('the request posts JSON to the object-creation endpoint', function () {
        $request = createDocumentPsrRequest(files: ['UploadID' => 1]);

        expect($request->getMethod())->toBe('POST')
            ->and($request->getHeaderLine('Content-Type'))->toBe('application/json')
            ->and($request->getUri()->getPath())->toBe('/REST/objects/0');
    });

    test('the body carries exactly the two documented keys', function () {
        // Anything extra would be silently ignored by the vault at best, so pin the
        // shape while the normalisation is in flux.
        $body = createDocumentBody(files: ['UploadID' => 1]);

        expect(array_keys($body))->toBe(['PropertyValues', 'Files']);
    });
});
