<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Responses;

use CodebarAg\MFiles\DTO\MFilesError;
use CodebarAg\MFiles\Support\JsonBody;
use JsonException;
use Saloon\Http\Response;

final class ErrorResponse
{
    /**
     * Maximum number of characters of a non-JSON error body to surface in the message.
     */
    private const BODY_SNIPPET_LENGTH = 500;

    public static function createDtoFromResponse(Response $response): MFilesError
    {
        return MFilesError::fromArray(self::payload($response));
    }

    /**
     * Decode the error payload, falling back to the HTTP envelope.
     *
     * Not every failure comes back as an M-Files JSON error: reverse proxies, load
     * balancers and IIS itself answer with HTML or an empty body for 401/429/5xx.
     * `Response::json()` decodes with JSON_THROW_ON_ERROR, so decoding those bodies
     * blindly used to throw a JsonException from inside the error path and hide the
     * real failure. We degrade to a synthetic payload instead.
     *
     * @return array<string, mixed>
     */
    private static function payload(Response $response): array
    {
        try {
            $decoded = JsonBody::decode($response);
        } catch (JsonException) {
            $decoded = null;
        }

        if (! is_array($decoded)) {
            $decoded = ['Message' => self::bodySnippet($response)];
        }

        // M-Files omits `Status` on some endpoints, and a proxy-generated body has no
        // M-Files fields at all. Backfill from the HTTP envelope so the resulting
        // exception still carries an actionable status code.
        if (! isset($decoded['Status'])) {
            $decoded['Status'] = $response->status();
        }

        if (! isset($decoded['Message']) && ! isset($decoded['Exception'])) {
            $decoded['Message'] = self::bodySnippet($response);
        }

        return $decoded;
    }

    private static function bodySnippet(Response $response): string
    {
        $body = trim(preg_replace('/\s+/', ' ', strip_tags($response->body())) ?? '');

        if ($body === '') {
            return sprintf('HTTP %d response with an empty body.', $response->status());
        }

        return mb_strimwidth($body, 0, self::BODY_SNIPPET_LENGTH, '…');
    }
}
