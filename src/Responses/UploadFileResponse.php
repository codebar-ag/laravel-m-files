<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Responses;

use CodebarAg\MFiles\DTO\MFilesError;
use CodebarAg\MFiles\Exceptions\MFilesErrorException;
use CodebarAg\MFiles\Support\JsonBody;
use Illuminate\Support\Arr;
use JsonException;
use Saloon\Http\Response;

final class UploadFileResponse
{
    /**
     * @return array<string, mixed>
     */
    public static function createDtoFromResponse(Response $response, string $fileName): array
    {
        if (! $response->successful()) {
            throw new MFilesErrorException(ErrorResponse::createDtoFromResponse($response));
        }

        $data = self::decode($response);

        // Only fills what M-Files left out. A correctly parsed multipart upload makes
        // M-Files derive Title/Extension from the part's filename itself, so these are
        // a fallback for vaults that do not return them — not an override.
        $data = Arr::add($data, 'Title', self::title($fileName));
        $data = Arr::add($data, 'Extension', self::extension($fileName));
        Arr::forget($data, 'FileInformationType');

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private static function decode(Response $response): array
    {
        try {
            $data = JsonBody::decode($response);
        } catch (JsonException $exception) {
            throw new MFilesErrorException(
                self::protocolError($response, 'The upload response was not valid JSON.'),
                $exception,
            );
        }

        if (! is_array($data)) {
            throw new MFilesErrorException(
                self::protocolError($response, 'The upload response did not contain a file object.')
            );
        }

        // Without an UploadID the staged upload cannot be referenced when creating the
        // document, so failing here points at the real cause instead of surfacing a
        // confusing error from the subsequent create request.
        if (! Arr::has($data, 'UploadID')) {
            throw new MFilesErrorException(
                self::protocolError($response, 'The upload response did not contain an UploadID.')
            );
        }

        return $data;
    }

    private static function protocolError(Response $response, string $message): MFilesError
    {
        return new MFilesError(
            errorCode: '',
            status: $response->status(),
            url: (string) $response->getPendingRequest()->getUri(),
            method: $response->getPendingRequest()->getMethod()->value,
            exceptionName: 'UnexpectedUploadResponse',
            exceptionMessage: $message,
            stack: null,
        );
    }

    /**
     * The filename without its extension.
     *
     * Uses pathinfo rather than a "before the last dot" split: for an extensionless
     * name such as "CHANGELOG" that split returned the whole name as the extension
     * too, so the file landed in M-Files as "CHANGELOG.CHANGELOG".
     */
    private static function title(string $fileName): string
    {
        return pathinfo(self::baseName($fileName), PATHINFO_FILENAME);
    }

    private static function extension(string $fileName): string
    {
        return pathinfo(self::baseName($fileName), PATHINFO_EXTENSION);
    }

    private static function baseName(string $fileName): string
    {
        return basename(str_replace('\\', '/', $fileName));
    }
}
