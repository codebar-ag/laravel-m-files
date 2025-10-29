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
        public readonly ?Exception $exception,
        public readonly ?string $stack,
        public readonly string $message,
        public readonly bool $isLoggedToVault,
        public readonly bool $isLoggedToApplication,
        public readonly string $exceptionName,
    ) {}

    public static function fromArray(array $data): self
    {
        $exceptionData = Arr::get($data, 'Exception');
        $exception = $exceptionData ? Exception::fromArray($exceptionData) : null;

        return new self(
            errorCode: Arr::get($data, 'ErrorCode', ''),
            status: Arr::get($data, 'Status', 0),
            url: Arr::get($data, 'URL', ''),
            method: Arr::get($data, 'Method', ''),
            exception: $exception,
            stack: Arr::get($data, 'Stack'),
            message: Arr::get($data, 'Message', ''),
            isLoggedToVault: Arr::get($data, 'IsLoggedToVault', false),
            isLoggedToApplication: Arr::get($data, 'IsLoggedToApplication', false),
            exceptionName: Arr::get($data, 'ExceptionName', ''),
        );
    }

    public function toArray(): array
    {
        return [
            'errorCode' => $this->errorCode,
            'status' => $this->status,
            'url' => $this->url,
            'method' => $this->method,
            'exception' => $this->exception?->toArray(),
            'stack' => $this->stack,
            'message' => $this->message,
            'isLoggedToVault' => $this->isLoggedToVault,
            'isLoggedToApplication' => $this->isLoggedToApplication,
            'exceptionName' => $this->exceptionName,
        ];
    }
}
