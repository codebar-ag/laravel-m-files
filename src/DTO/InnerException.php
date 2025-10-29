<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\DTO;

use Illuminate\Support\Arr;

final class InnerException
{
    public function __construct(
        public readonly string $name,
        public readonly string $message,
        public readonly ?string $stackText,
        public readonly ?string $errorCode,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: Arr::get($data, 'Name', ''),
            message: Arr::get($data, 'Message', ''),
            stackText: Arr::get($data, 'StackText'),
            errorCode: Arr::get($data, 'ErrorCode'),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'message' => $this->message,
            'stackText' => $this->stackText,
            'errorCode' => $this->errorCode,
        ];
    }
}
