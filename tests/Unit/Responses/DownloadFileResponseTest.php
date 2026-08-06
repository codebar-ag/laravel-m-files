<?php

declare(strict_types=1);

use CodebarAg\MFiles\DTO\DownloadedFile;
use CodebarAg\MFiles\Requests\DownloadFileRequest;
use CodebarAg\MFiles\Responses\DownloadFileResponse;
use Saloon\Http\Faking\MockResponse;

/**
 * @param  array<string, string>  $headers
 */
function downloadedFile(string $body = 'file-bytes', array $headers = []): DownloadedFile
{
    $response = mFilesSend(
        MockResponse::make($body, 200, $headers),
        new DownloadFileRequest(objectType: 0, objectId: 1, objectVersion: 1, fileId: 1),
    );

    return DownloadFileResponse::createDtoFromResponse($response);
}

describe('Content-Disposition filename parsing', function () {
    test('reads the RFC 5987 form with an uppercase charset', function () {
        // M-Files sends "UTF-8" uppercase. The previous pattern was case-sensitive, so
        // this fell through to the plain branch and kept "UTF-8''" in the filename.
        $file = downloadedFile(headers: [
            'Content-Disposition' => "attachment; filename*=UTF-8''Rechnung%20M%C3%BCller.pdf",
        ]);

        expect($file->name)->toBe('Rechnung Müller')
            ->and($file->extension)->toBe('pdf');
    });

    test('reads the RFC 5987 form with a lowercase charset', function () {
        $file = downloadedFile(headers: [
            'Content-Disposition' => "attachment; filename*=utf-8''Bericht%20Q1.docx",
        ]);

        expect($file->name)->toBe('Bericht Q1')
            ->and($file->extension)->toBe('docx');
    });

    test('reads the RFC 5987 form that carries a language tag', function () {
        $file = downloadedFile(headers: [
            'Content-Disposition' => "attachment; filename*=UTF-8'de'Angebot.pdf",
        ]);

        expect($file->name)->toBe('Angebot');
    });

    test('prefers the extended form when both are present', function () {
        $file = downloadedFile(headers: [
            'Content-Disposition' => "attachment; filename=\"Angebot.pdf\"; filename*=UTF-8''Angebot%20M%C3%BCller.pdf",
        ]);

        expect($file->name)->toBe('Angebot Müller');
    });

    test('does not percent-decode the plain form', function () {
        // The plain form is not percent-encoded, so decoding it turned a literal "%20"
        // in a filename into a space and a literal "+" into a space as well.
        $file = downloadedFile(headers: [
            'Content-Disposition' => 'attachment; filename="Q1%20growth+final.pdf"',
        ]);

        expect($file->name)->toBe('Q1%20growth+final')
            ->and($file->extension)->toBe('pdf');
    });

    test('reads an unquoted plain filename', function () {
        $file = downloadedFile(headers: [
            'Content-Disposition' => 'attachment; filename=report.pdf',
        ]);

        expect($file->name)->toBe('report')
            ->and($file->extension)->toBe('pdf');
    });

    test('handles a filename with several dots', function () {
        $file = downloadedFile(headers: [
            'Content-Disposition' => 'attachment; filename="archive.tar.gz"',
        ]);

        expect($file->name)->toBe('archive.tar')
            ->and($file->extension)->toBe('gz');
    });

    test('returns a null extension for an extensionless filename', function () {
        $file = downloadedFile(headers: [
            'Content-Disposition' => 'attachment; filename="CHANGELOG"',
        ]);

        expect($file->name)->toBe('CHANGELOG')
            ->and($file->extension)->toBeNull();
    });

    test('strips a path so the filename cannot escape its directory', function () {
        $file = downloadedFile(headers: [
            'Content-Disposition' => 'attachment; filename="../../etc/passwd"',
        ]);

        expect($file->name)->toBe('passwd');
    });

    test('returns null when the header is absent', function () {
        $file = downloadedFile();

        expect($file->name)->toBeNull()
            ->and($file->extension)->toBeNull();
    });

    test('returns null when the header carries no filename', function () {
        $file = downloadedFile(headers: ['Content-Disposition' => 'attachment']);

        expect($file->name)->toBeNull();
    });
});

describe('size', function () {
    test('reports the length of the bytes actually received', function () {
        $file = downloadedFile('0123456789');

        expect($file->size)->toBe(10)
            ->and($file->content)->toBe('0123456789');
    });

    test('ignores a Content-Length that disagrees with the body', function () {
        // Content-Length reports the compressed size when the transport gzipped the
        // response, and is absent entirely on chunked responses.
        $file = downloadedFile('0123456789', ['Content-Length' => '4']);

        expect($file->size)->toBe(10);
    });

    test('handles an empty body', function () {
        $file = downloadedFile('');

        expect($file->size)->toBe(0)
            ->and($file->content)->toBe('');
    });

    test('keeps binary content byte-identical', function () {
        $binary = random_bytes(512)."\x00\r\n\xFF";

        $file = downloadedFile($binary);

        expect($file->content)->toBe($binary)
            ->and($file->size)->toBe(strlen($binary));
    });
});
