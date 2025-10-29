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
        $fileContentType = $headers->get('Content-Type');
        $fileSize = (int) $headers->get('Content-Length', 0);
        $contentDisposition = $headers->get('Content-Disposition', '');
        $fileName = self::extractFilenameFromContentDisposition($contentDisposition);
        $fileExtension = $fileName ? pathinfo($fileName, PATHINFO_EXTENSION) : null;
        $fileNameWithoutExtension = $fileName ? pathinfo($fileName, PATHINFO_FILENAME) : null;

        return new DownloadedFile(
            name: $fileNameWithoutExtension,
            extension: $fileExtension,
            size: $fileSize,
            contentType: $fileContentType,
            content: $response->body(),
        );
    }

    private static function extractFilenameFromContentDisposition(string $contentDisposition): ?string
    {
        if (empty($contentDisposition)) {
            return null;
        }

        if (preg_match('/filename\*=utf-8\'\'([^;]+)/', $contentDisposition, $matches)) {
            return urldecode($matches[1]);
        }

        if (preg_match('/filename[^;=\n]*=(([\'"]).*?\2|[^;\n]*)/', $contentDisposition, $matches)) {
            $filename = trim($matches[1], '"\'');

            return urldecode($filename);
        }

        return null;
    }
}
