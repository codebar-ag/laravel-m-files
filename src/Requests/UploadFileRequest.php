<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Requests;

use CodebarAg\MFiles\Responses\UploadFileResponse;
use Saloon\Contracts\Body\HasBody;
use Saloon\Data\MultipartValue;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasMultipartBody;

class UploadFileRequest extends Request implements HasBody
{
    use HasMultipartBody;

    protected Method $method = Method::POST;

    public function __construct(
        public string $fileContent,
        public string $fileName,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/files';
    }

    protected function defaultBody(): array
    {
        return [
            'file' => new MultipartValue(
                name: 'file',
                value: $this->fileContent,
                filename: $this->fileName
            ),
        ];
    }

    public function createDtoFromResponse(Response $response): array
    {
        return UploadFileResponse::createDtoFromResponse($response, $this->fileName);
    }
}
