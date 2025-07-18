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
        public int $objectId,
        public int $fileId,
        public ?int $objectTypeId = null,
        public ?bool $includeDeleted = null,
    ) {
        if ($this->objectId <= 0) {
            throw new \InvalidArgumentException('Object ID must be a positive integer');
        }

        if ($this->fileId <= 0) {
            throw new \InvalidArgumentException('File ID must be a positive integer');
        }
    }

    public function resolveEndpoint(): string
    {
        return "/objects/{$this->objectId}/files/{$this->fileId}/content";
    }

    protected function defaultQuery(): array
    {
        return array_filter([
            'objectType' => $this->objectTypeId,
            'includeDeleted' => $this->includeDeleted ? 'true' : 'false',
        ], fn ($value) => $value !== null);
    }

    protected function defaultHeaders(): array
    {
        return [
            'Accept' => '*/*',
        ];
    }

    public function createDtoFromResponse(\Saloon\Http\Response $response): DownloadedFile
    {
        return DownloadFileResponse::createDtoFromResponse($response);
    }
} 