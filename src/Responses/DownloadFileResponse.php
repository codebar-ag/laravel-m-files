<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Responses;

use CodebarAg\MFiles\DTO\DownloadedFile;
use Saloon\Http\Response;

final class DownloadFileResponse
{
    public static function createDtoFromResponse(Response $response): DownloadedFile
    {
        $headers = $response->headers();
        
        // Extract file metadata from response headers
        $contentDisposition = $headers->get('Content-Disposition', '');
        $contentLength = $headers->get('Content-Length');
        $lastModified = $headers->get('Last-Modified');
        
        // Parse filename from Content-Disposition header
        $filename = self::extractFilenameFromContentDisposition($contentDisposition);
        $extension = $filename ? pathinfo($filename, PATHINFO_EXTENSION) : null;
        $name = $filename ? pathinfo($filename, PATHINFO_FILENAME) : null;
        $contentType = $headers->get('Content-Type');
        
        return new DownloadedFile(
            content: $response->body(),
            name: $name,
            extension: $extension,
            size: $contentLength ? (int) $contentLength : null,
            lastModified: $lastModified ? \Carbon\CarbonImmutable::parse($lastModified) : null,
            contentType: $contentType,
        );
    }
    
    private static function extractFilenameFromContentDisposition(string $contentDisposition): ?string
    {
        if (empty($contentDisposition)) {
            return null;
        }
        
        // Parse Content-Disposition header to extract filename
        if (preg_match('/filename[^;=\n]*=(([\'"]).*?\2|[^;\n]*)/', $contentDisposition, $matches)) {
            $filename = trim($matches[1], '"\'');
            return $filename;
        }
        
        return null;
    }
} 