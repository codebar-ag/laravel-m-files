<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Responses;

use CodebarAg\MFiles\DTO\MFilesError;
use CodebarAg\MFiles\DTO\ObjectProperties;
use CodebarAg\MFiles\Exceptions\MFilesErrorException;
use CodebarAg\MFiles\Support\JsonBody;
use JsonException;
use Saloon\Http\Response;

final class ObjectPropertiesResponse
{
    public static function createDtoFromResponse(Response $response): ObjectProperties
    {
        if (! $response->successful()) {
            throw new MFilesErrorException(ErrorResponse::createDtoFromResponse($response));
        }

        try {
            $data = JsonBody::decode($response);
        } catch (JsonException $exception) {
            throw new MFilesErrorException(
                self::protocolError($response, 'The object response was not valid JSON.'),
                $exception,
            );
        }

        // A 200 carrying a non-object body usually means a proxy or SSO layer answered
        // in place of M-Files. Surfacing it as MFilesErrorException keeps a single
        // catchable type for callers instead of a TypeError raised inside the DTO.
        if (! is_array($data)) {
            throw new MFilesErrorException(
                self::protocolError($response, 'The object response did not contain an object payload.')
            );
        }

        return ObjectProperties::fromArray($data);
    }

    private static function protocolError(Response $response, string $message): MFilesError
    {
        return new MFilesError(
            errorCode: '',
            status: $response->status(),
            url: (string) $response->getPendingRequest()->getUri(),
            method: $response->getPendingRequest()->getMethod()->value,
            exceptionName: 'UnexpectedObjectResponse',
            exceptionMessage: $message,
            stack: null,
        );
    }
}
