<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Plugins\AcceptsJson;

class GetCurrentUserRequest extends Request
{
    use AcceptsJson;

    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/session';
    }

    public function createDtoFromResponse(\Saloon\Http\Response $response): \CodebarAg\MFiles\DTO\User
    {
        return \CodebarAg\MFiles\Responses\GetCurrentUserResponse::createDtoFromResponse($response);
    }
}
