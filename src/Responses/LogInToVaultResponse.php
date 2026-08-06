<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Responses;

use CodebarAg\MFiles\DTO\MFilesError;
use CodebarAg\MFiles\Exceptions\MFilesErrorException;
use CodebarAg\MFiles\Support\JsonBody;
use Illuminate\Support\Arr;
use JsonException;
use Saloon\Http\Response;

final class LogInToVaultResponse
{
    /**
     * Return the authentication token.
     *
     * A missing token is now an exception rather than a silent null. The null was
     * cached against the auth key, so every subsequent request re-authenticated and
     * then went out with an empty X-Authentication header — an auth storm that
     * surfaced as unexplained 401s far away from the actual cause.
     *
     * LogInToVaultRequest::createDtoFromResponse() keeps its nullable return type, so
     * this narrowing is invisible to callers.
     */
    public static function createDtoFromResponse(Response $response): string
    {
        if (! $response->successful()) {
            throw new MFilesErrorException(ErrorResponse::createDtoFromResponse($response));
        }

        try {
            $data = JsonBody::decode($response);
        } catch (JsonException $exception) {
            throw new MFilesErrorException(
                self::authError($response, 'The authentication response was not valid JSON.'),
                $exception,
            );
        }

        $token = is_array($data) ? Arr::get($data, 'Value') : null;

        if (! is_string($token) || trim($token) === '') {
            throw new MFilesErrorException(
                self::authError($response, 'M-Files did not return an authentication token.')
            );
        }

        return $token;
    }

    private static function authError(Response $response, string $message): MFilesError
    {
        return new MFilesError(
            errorCode: '',
            status: $response->status(),
            url: (string) $response->getPendingRequest()->getUri(),
            method: $response->getPendingRequest()->getMethod()->value,
            exceptionName: 'UnexpectedAuthenticationResponse',
            exceptionMessage: $message,
            stack: null,
        );
    }
}
