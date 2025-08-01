<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetVaultsRequest extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/session/vaults';
    }
}
