<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\DTO;

use Illuminate\Support\Arr;

final class MFilesError
{
    public function __construct(
        public readonly string $errorCode,
        public readonly int $status,
        public readonly string $url,
        public readonly string $method,
        public readonly string $exceptionName,
        public readonly string $exceptionMessage,
        public readonly ?string $stack,
    ) {}

    public static function fromArray(array $data): self
    {
        $exceptionData = Arr::get($data, 'Exception', []);

        return new self(
            errorCode: Arr::get($data, 'ErrorCode', ''),
            status: Arr::get($data, 'Status', 0),
            url: Arr::get($data, 'URL', ''),
            method: Arr::get($data, 'Method', ''),
            exceptionName: Arr::get($exceptionData, 'Name', ''),
            exceptionMessage: Arr::get($exceptionData, 'Message', ''),
            stack: Arr::get($data, 'Stack'),
        );
    }

    public function toArray(): array
    {
        return [
            'errorCode' => $this->errorCode,
            'status' => $this->status,
            'url' => $this->url,
            'method' => $this->method,
            'exceptionName' => $this->exceptionName,
            'exceptionMessage' => $this->exceptionMessage,
            'stack' => $this->stack,
        ];
    }
}
