<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Support;

use JsonException;
use Saloon\Http\Response;

/**
 * Decodes a response body as JSON.
 *
 * Saloon's Response::json() is declared as returning an array, but json_decode
 * genuinely returns a scalar for a body such as `"token"` or `42` — a shape M-Files
 * itself never sends, yet proxies and SSO interstitials do. Decoding here keeps the
 * return type honestly `mixed` so callers must handle it.
 *
 * @internal
 */
final class JsonBody
{
    /**
     * @return mixed The decoded payload, or null when the body is empty.
     *
     * @throws JsonException When the body is not valid JSON.
     */
    public static function decode(Response $response): mixed
    {
        $body = trim($response->body());

        if ($body === '') {
            return null;
        }

        return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    }
}
