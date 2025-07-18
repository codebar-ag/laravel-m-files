<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Responses;

use CodebarAg\MFiles\DTO\User;
use Saloon\Http\Response;

final class GetCurrentUserResponse
{
    public static function createDtoFromResponse(Response $response): User
    {
        $data = $response->json();

        return User::fromArray($data);
    }
}
