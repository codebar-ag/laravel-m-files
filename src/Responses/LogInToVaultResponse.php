<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Responses;

use Illuminate\Support\Arr;
use Saloon\Http\Response;

final class LogInToVaultResponse
{
    public static function createDtoFromResponse(Response $response): ?string
    {
        return Arr::get($response->json(), 'Value');
    }
}
