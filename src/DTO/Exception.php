<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\DTO;

use Illuminate\Support\Arr;

final class Exception
{
    public function __construct(
        public readonly string $name,
        public readonly string $message,
        public readonly ?InnerException $innerException,
    ) {}

    public static function fromArray(array $data): self
    {
        $innerExceptionData = Arr::get($data, 'InnerException');
        $innerException = $innerExceptionData ? InnerException::fromArray($innerExceptionData) : null;

        return new self(
            name: Arr::get($data, 'Name', ''),
            message: Arr::get($data, 'Message', ''),
            innerException: $innerException,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'message' => $this->message,
            'innerException' => $this->innerException?->toArray(),
        ];
    }
}
