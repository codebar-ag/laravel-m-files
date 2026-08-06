<?php

declare(strict_types=1);

use CodebarAg\MFiles\Connectors\MFilesConnector;
use CodebarAg\MFiles\Requests\LogInToVaultRequest;
use CodebarAg\MFiles\Requests\SetPropertiesRequest;
use CodebarAg\MFiles\Requests\UploadFileRequest;
use Psr\Http\Message\RequestInterface;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;

/**
 * Regression cover for the upload corruption bug.
 *
 * The connector used to publish `Content-Type: application/json` for every request.
 * Connector headers are merged after the body plugins boot, so that header replaced
 * the `multipart/form-data; boundary=…` header UploadFileRequest had just set — the
 * boundary was lost, M-Files could not split the envelope, and it stored the entire
 * multipart body as the file. PDFs still opened (readers scan for %PDF and rebuild
 * the trailer) which is why it went unnoticed, but ZIP archives failed their CRC.
 */
beforeEach(function () {
    // Building a PendingRequest runs the mock-resolution middleware, so every request
    // the tests construct needs an entry even though only the outgoing PSR request is
    // inspected.
    Saloon::fake([
        LogInToVaultRequest::class => MockResponse::make(['Value' => 'fake-token'], 200),
        UploadFileRequest::class => MockResponse::make(['UploadID' => 1, 'Size' => 1], 200),
        SetPropertiesRequest::class => MockResponse::make([], 200),
    ]);
});

/**
 * Build the request exactly as the connector would send it on the wire.
 */
function wireRequestFor(string $content, string $fileName): RequestInterface
{
    return (new MFilesConnector(mFilesConfig()))
        ->createPendingRequest(new UploadFileRequest(fileContent: $content, fileName: $fileName))
        ->createPsrRequest();
}

function boundaryOf(RequestInterface $request): ?string
{
    $matched = preg_match('/boundary=(.+)$/', $request->getHeaderLine('Content-Type'), $matches);

    return $matched === 1 ? $matches[1] : null;
}

test('the upload advertises a multipart content type carrying a boundary', function () {
    $request = wireRequestFor('some bytes', 'archive.zip');

    expect($request->getHeaderLine('Content-Type'))->toStartWith('multipart/form-data; boundary=')
        ->and(boundaryOf($request))->not->toBeNull();
})->group('upload-file');

test('the connector no longer overrides the multipart content type with application/json', function () {
    $request = wireRequestFor('some bytes', 'archive.zip');

    expect($request->getHeaderLine('Content-Type'))->not->toBe('application/json');
})->group('upload-file');

test('a zip survives the multipart envelope byte for byte', function () {
    // A real archive: any single flipped byte fails the CRC32 check, which is exactly
    // what made this bug visible for ZIP but not for PDF.
    $zipPath = tempnam(sys_get_temp_dir(), 'mfiles-').'.zip';
    $archive = new ZipArchive;
    $archive->open($zipPath, ZipArchive::CREATE);
    $archive->addFromString('payload.txt', str_repeat('binary payload ', 100));
    $archive->close();

    $zip = file_get_contents($zipPath);
    unlink($zipPath);

    $request = wireRequestFor($zip, 'archive.zip');
    $boundary = boundaryOf($request);
    $body = (string) $request->getBody();

    // Assert the boundary exists before using it: without this the split below falls
    // back to splitting on a bare "--", which happens to succeed even when the
    // Content-Type header is wrong.
    expect($boundary)->not->toBeNull();

    // Reproduce what a server does: split on the advertised boundary and take the
    // bytes after the part headers.
    $part = explode('--'.$boundary, $body)[1];
    $extracted = substr(explode("\r\n\r\n", $part, 2)[1], 0, -2);

    expect($extracted)->toBe($zip)
        ->and(strlen($extracted))->toBe(strlen($zip));

    // And the extracted archive must still be readable.
    $roundTripPath = tempnam(sys_get_temp_dir(), 'mfiles-roundtrip-').'.zip';
    file_put_contents($roundTripPath, $extracted);

    $verify = new ZipArchive;
    expect($verify->open($roundTripPath, ZipArchive::CHECKCONS))->toBeTrue()
        ->and($verify->getFromName('payload.txt'))->toBe(str_repeat('binary payload ', 100));

    $verify->close();
    unlink($roundTripPath);
})->group('upload-file');

test('binary content with null bytes and CRLF sequences is not mangled', function () {
    // Bytes chosen to break naive string handling: nulls, bare CR/LF, high bytes and
    // a sequence that is invalid UTF-8.
    $content = "\x00\x01\r\n--notaboundary\r\n\xFF\xFE\x80binary\x00tail";

    $request = wireRequestFor($content, 'payload.bin');
    $boundary = boundaryOf($request);
    $body = (string) $request->getBody();

    $part = explode('--'.$boundary, $body)[1];
    $extracted = substr(explode("\r\n\r\n", $part, 2)[1], 0, -2);

    expect($extracted)->toBe($content);
})->group('upload-file');

test('json requests still send application/json', function () {
    $request = (new MFilesConnector(mFilesConfig()))
        ->createPendingRequest(new SetPropertiesRequest(
            objectType: 0,
            objectId: 1,
            objectVersion: 1,
            propertyValues: [],
        ))
        ->createPsrRequest();

    expect($request->getHeaderLine('Content-Type'))->toBe('application/json');
})->group('upload-file');
