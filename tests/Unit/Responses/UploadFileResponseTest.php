<?php

declare(strict_types=1);

use CodebarAg\MFiles\Exceptions\MFilesErrorException;
use CodebarAg\MFiles\Requests\UploadFileRequest;
use CodebarAg\MFiles\Responses\UploadFileResponse;
use Illuminate\Support\Arr;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Response;

/**
 * @param  array<string, mixed>|string  $body
 */
function uploadResponse(array|string $body, int $status = 200): Response
{
    return mFilesSend(
        MockResponse::make($body, $status),
        new UploadFileRequest(fileContent: 'x', fileName: 'x.pdf'),
    );
}

/**
 * The staged-upload payload M-Files answers a multipart POST /files with, plus the
 * filename the caller uploaded under.
 *
 * @param  array<string, mixed>|string  $body
 * @return array<string, mixed>
 */
function uploadDto(array|string $body, string $fileName, int $status = 200): array
{
    return UploadFileResponse::createDtoFromResponse(uploadResponse($body, $status), $fileName);
}

/**
 * The failure raised while parsing an upload response, or null when it succeeded.
 *
 * @param  array<string, mixed>|string  $body
 */
function uploadFailure(array|string $body, int $status = 200): ?MFilesErrorException
{
    try {
        uploadDto($body, 'test-1.pdf', $status);
    } catch (MFilesErrorException $exception) {
        return $exception;
    }

    return null;
}

describe('Title and Extension fallback', function () {
    test('splits a plain filename', function () {
        $dto = uploadDto(['UploadID' => 1, 'Size' => 8785], 'test-1.pdf');

        expect(Arr::get($dto, 'Title'))->toBe('test-1')
            ->and(Arr::get($dto, 'Extension'))->toBe('pdf');
    });

    test('leaves the extension empty for an extensionless filename', function () {
        // Str::beforeLast/afterLast return the whole subject when the delimiter is
        // absent, so "CHANGELOG" arrived with Title AND Extension set to "CHANGELOG"
        // and the document landed in the vault as "CHANGELOG.CHANGELOG".
        $dto = uploadDto(['UploadID' => 1, 'Size' => 12], 'CHANGELOG');

        expect(Arr::get($dto, 'Title'))->toBe('CHANGELOG')
            ->and(Arr::get($dto, 'Extension'))->toBe('');
    });

    test('keeps every dot but the last in the title', function () {
        $dto = uploadDto(['UploadID' => 2], 'archive.tar.gz');

        expect(Arr::get($dto, 'Title'))->toBe('archive.tar')
            ->and(Arr::get($dto, 'Extension'))->toBe('gz');
    });

    test('reduces a filename carrying a path to its basename', function () {
        // A caller passing the path it read the file from must not leak directory
        // names into the M-Files title.
        $dto = uploadDto(['UploadID' => 3], 'folder/sub/report.pdf');

        expect(Arr::get($dto, 'Title'))->toBe('report')
            ->and(Arr::get($dto, 'Extension'))->toBe('pdf');
    });

    test('reduces a Windows path to its basename', function () {
        $dto = uploadDto(['UploadID' => 4], 'C:\\Users\\Administrator\\Documents\\Lohnausweis.pdf');

        expect(Arr::get($dto, 'Title'))->toBe('Lohnausweis')
            ->and(Arr::get($dto, 'Extension'))->toBe('pdf');
    });

    test('does not let a traversal segment become the title', function () {
        $dto = uploadDto(['UploadID' => 5], '../../etc/passwd');

        expect(Arr::get($dto, 'Title'))->toBe('passwd')
            ->and(Arr::get($dto, 'Extension'))->toBe('');
    });

    test('documents how a leading-dot filename is split', function () {
        // Known gap, pinned so any change to it is deliberate: pathinfo treats the
        // leading dot of ".gitignore" as an extension separator, so the title comes out
        // empty and the whole name becomes the extension. Uploading a dotfile therefore
        // still produces a titleless object. DownloadFileResponse splits the same way,
        // so at least the two directions agree.
        $dto = uploadDto(['UploadID' => 6], '.gitignore');

        expect(Arr::get($dto, 'Title'))->toBe('')
            ->and(Arr::get($dto, 'Extension'))->toBe('gitignore');
    });

    test('does not override a Title M-Files itself returned', function () {
        // A correctly parsed multipart part makes M-Files derive the name from the
        // filename it received. Overwriting that would undo any normalisation the
        // vault applied, so the fallback only fills what is missing.
        $dto = uploadDto([
            'UploadID' => 7,
            'Size' => 100,
            'Title' => 'Vault Chosen Title',
            'Extension' => 'docx',
        ], 'ignored-local-name.pdf');

        expect(Arr::get($dto, 'Title'))->toBe('Vault Chosen Title')
            ->and(Arr::get($dto, 'Extension'))->toBe('docx');
    });

    test('does not override a Title M-Files returned as an empty string', function () {
        // Arr::add only fills keys that are absent, so an explicit "" stays put.
        $dto = uploadDto(['UploadID' => 8, 'Title' => '', 'Extension' => ''], 'report.pdf');

        expect(Arr::get($dto, 'Title'))->toBe('')
            ->and(Arr::get($dto, 'Extension'))->toBe('');
    });

    test('fills only the half M-Files left out', function () {
        $dto = uploadDto(['UploadID' => 9, 'Title' => 'Vault Chosen Title'], 'report.pdf');

        expect(Arr::get($dto, 'Title'))->toBe('Vault Chosen Title')
            ->and(Arr::get($dto, 'Extension'))->toBe('pdf');
    });
});

describe('payload shaping', function () {
    test('drops FileInformationType from the returned array', function () {
        // It is an internal M-Files discriminator; forwarding it into the subsequent
        // create-document call makes the vault reject the upload object.
        $dto = uploadDto(['UploadID' => 1, 'Size' => 8785, 'FileInformationType' => 0], 'test-1.pdf');

        expect($dto)->not->toHaveKey('FileInformationType')
            ->and($dto)->toHaveKey('UploadID')
            ->and(Arr::get($dto, 'Size'))->toBe(8785);
    });

    test('passes every other key through untouched', function () {
        $dto = uploadDto([
            'UploadID' => 42,
            'Size' => 8785,
            'FileInformationType' => 0,
        ], 'test-1.pdf');

        expect($dto)->toBe([
            'UploadID' => 42,
            'Size' => 8785,
            'Title' => 'test-1',
            'Extension' => 'pdf',
        ]);
    });
});

describe('unexpected upload responses', function () {
    test('a 200 with a non-JSON body raises MFilesErrorException', function () {
        // An SSO interstitial or proxy error page answers 200 with HTML. Decoding it
        // used to surface a raw JsonException from inside the upload path.
        expect(fn () => uploadDto('<html><body>Session expired</body></html>', 'test-1.pdf'))
            ->toThrow(MFilesErrorException::class);
    });

    test('a 200 with a JSON scalar body raises MFilesErrorException', function () {
        // json_decode happily returns a scalar here; the array type hint downstream
        // then blew up with a TypeError instead of a diagnosable error.
        expect(fn () => uploadDto('42', 'test-1.pdf'))
            ->toThrow(MFilesErrorException::class);
    });

    test('a 200 with an empty body raises MFilesErrorException', function () {
        expect(fn () => uploadDto('', 'test-1.pdf'))
            ->toThrow(MFilesErrorException::class);
    });

    test('a 200 without an UploadID names the missing field', function () {
        // Without it the staged upload cannot be referenced when the document is
        // created, so failing here beats a confusing error one request later.
        expect(fn () => uploadDto(['Size' => 8785, 'FileInformationType' => 0], 'test-1.pdf'))
            ->toThrow(MFilesErrorException::class, 'The upload response did not contain an UploadID.');
    });

    test('an unsuccessful status raises MFilesErrorException carrying the status', function () {
        $exception = uploadFailure(
            ['Status' => 500, 'Exception' => ['Name' => 'InternalError', 'Message' => 'Upload failed']],
            500,
        );

        expect($exception)->toBeInstanceOf(MFilesErrorException::class)
            ->and($exception?->status())->toBe(500)
            ->and($exception?->getMessage())->toBe('Upload failed');
    });

    test('the protocol failure carries the originating request context', function () {
        $exception = uploadFailure('<html>nope</html>');

        expect($exception)->toBeInstanceOf(MFilesErrorException::class)
            ->and($exception?->error->method)->toBe('POST')
            ->and($exception?->error->url)->toContain('/files')
            ->and($exception?->error->exceptionName)->toBe('UnexpectedUploadResponse')
            // The HTTP envelope said 200; the failure is a protocol violation, not a
            // status code, so the status is reported as-received.
            ->and($exception?->error->status)->toBe(200);
    });
});
