<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Requests;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
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

    public function createDtoFromResponse(\Saloon\Http\Response $response): array
    {
        $data = $response->json();
        $data = Arr::add($data, 'Title', Str::beforeLast($this->fileName, '.'));
        $data = Arr::add($data, 'Extension', Str::afterLast($this->fileName, '.'));
        Arr::forget($data, 'FileInformationType');

        return $data;
    }
}
