<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Data\MultipartValue;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasMultipartBody;

class UploadFileRequest extends Request implements HasBody
{
    use HasMultipartBody;

    protected Method $method = Method::POST;

    public function __construct(
        public string $fileContent,
        public string $fileName,
    ) {
        if (empty($this->fileContent)) {
            throw new \InvalidArgumentException('File content is required');
        }

        if (empty($this->fileName)) {
            throw new \InvalidArgumentException('File name is required');
        }
    }

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

    public function createDtoFromResponse(\Saloon\Http\Response $response): array
    {
        $data = $response->json();

        $data['Title'] = \Illuminate\Support\Str::beforeLast($this->fileName, '.');
        $data['Extension'] = \Illuminate\Support\Str::afterLast($this->fileName, '.');

        unset($data['FileInformationType']);

        return $data;
    }
}
