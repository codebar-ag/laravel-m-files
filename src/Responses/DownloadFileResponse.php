<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Responses;

use CodebarAg\MFiles\DTO\DownloadedFile;
use CodebarAg\MFiles\Exceptions\MFilesErrorException;
use Saloon\Http\Response;

final class DownloadFileResponse
{
    public static function createDtoFromResponse(Response $response): DownloadedFile
    {
        if (! $response->successful()) {
            throw new MFilesErrorException(ErrorResponse::createDtoFromResponse($response));
        }

        $headers = $response->headers();
        $content = $response->body();

        $fileName = self::extractFilenameFromContentDisposition(
            $headers->get('Content-Disposition', '') ?? ''
        );

        return new DownloadedFile(
            name: $fileName !== null ? pathinfo($fileName, PATHINFO_FILENAME) : null,
            extension: $fileName !== null ? self::extension($fileName) : null,
            // Measured from the bytes actually held rather than taken from
            // Content-Length: that header is absent on chunked responses and still
            // reports the *compressed* length when the transport applied gzip, so it
            // disagreed with strlen($content) exactly when callers needed it most.
            size: strlen($content),
            contentType: $headers->get('Content-Type'),
            content: $content,
        );
    }

    /**
     * Return the extension, or null for a file that genuinely has none.
     */
    private static function extension(string $fileName): ?string
    {
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        return $extension === '' ? null : $extension;
    }

    /**
     * Resolve the filename from a Content-Disposition header.
     *
     * Handles both the RFC 5987 extended form (`filename*=UTF-8''Rechnung%20M%C3%BCller.pdf`)
     * and the plain form (`filename="report.pdf"`). The extended form is the only one
     * that is percent-encoded — decoding the plain form as well used to mangle any
     * literal `%` or `+` in a filename.
     */
    private static function extractFilenameFromContentDisposition(string $contentDisposition): ?string
    {
        if (trim($contentDisposition) === '') {
            return null;
        }

        // RFC 5987: filename*=<charset>'<language>'<percent-encoded-value>.
        // M-Files sends the charset uppercase ("UTF-8"), which the previous
        // case-sensitive pattern missed — the value then fell through to the plain
        // branch below and kept its "UTF-8''" prefix as part of the filename.
        if (preg_match('/filename\*\s*=\s*([^\';]*)\'([^\';]*)\'([^;]+)/i', $contentDisposition, $matches) === 1) {
            $decoded = rawurldecode(trim($matches[3]));

            $name = self::normalise($decoded, $matches[1]);

            if ($name !== null) {
                return $name;
            }
        }

        if (preg_match('/filename\s*=\s*("([^"]*)"|\'([^\']*)\'|[^;\r\n]*)/i', $contentDisposition, $matches) === 1) {
            $filename = $matches[2] ?? '';

            if ($filename === '') {
                $filename = $matches[3] ?? '';
            }

            if ($filename === '') {
                $filename = trim($matches[1]);
            }

            return self::normalise($filename, null);
        }

        return null;
    }

    /**
     * Strip any path component and reject values that are not usable as a filename.
     *
     * M-Files never sends a path here, but a filename is frequently concatenated into
     * a storage path by callers, so a stray `../` or directory prefix must not survive.
     */
    private static function normalise(string $filename, ?string $charset): ?string
    {
        if ($charset !== null && $charset !== '' && strcasecmp($charset, 'utf-8') !== 0) {
            $converted = @mb_convert_encoding($filename, 'UTF-8', $charset);

            if (is_string($converted) && $converted !== '') {
                $filename = $converted;
            }
        }

        // Guard against both separators regardless of host platform.
        $filename = str_replace('\\', '/', $filename);
        $filename = basename($filename);
        $filename = trim($filename);

        if ($filename === '' || $filename === '.' || $filename === '..') {
            return null;
        }

        return $filename;
    }
}
