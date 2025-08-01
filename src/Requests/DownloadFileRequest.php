<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Requests;

use CodebarAg\MFiles\DTO\DownloadedFile;
use CodebarAg\MFiles\Responses\DownloadFileResponse;
use Saloon\Enums\Method;
use Saloon\Http\Request;

class DownloadFileRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        public int $objectType,
        public int $objectId,
        public int $objectVersion,
        public int $fileId,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/objects/{$this->objectType}/{$this->objectId}/{$this->objectVersion}/files/{$this->fileId}/content";
    }

    public function createDtoFromResponse(\Saloon\Http\Response $response): DownloadedFile
    {
        return DownloadFileResponse::createDtoFromResponse($response);
    }
}
