<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Responses;

use CodebarAg\MFiles\DTO\Document;
use Saloon\Http\Response;

final class CreateSingleFileDocumentResponse
{
    public static function createDtoFromResponse(Response $response): Document
    {
        $data = $response->json();

        return Document::fromArray($data);
    }
}
