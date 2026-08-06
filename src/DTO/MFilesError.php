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

    /**
     * Build the DTO from a decoded M-Files error payload.
     *
     * M-Files is inconsistent about the shape of this payload: `Stack` is a string on
     * some vaults and a list of frames on others, `Status` can arrive as a numeric
     * string, and the `Exception` envelope is absent on some endpoints (which put
     * `Message` / `ExceptionName` at the top level instead). Every field is therefore
     * coerced defensively — this DTO is built on the failure path, so it must never
     * throw and mask the original error.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $exceptionData = Arr::get($data, 'Exception');

        if (! is_array($exceptionData)) {
            $exceptionData = [];
        }

        return new self(
            errorCode: self::toString(Arr::get($data, 'ErrorCode')) ?? '',
            status: self::toInt(Arr::get($data, 'Status')),
            url: self::toString(Arr::get($data, 'URL')) ?? '',
            method: self::toString(Arr::get($data, 'Method')) ?? '',
            exceptionName: self::toString(Arr::get($exceptionData, 'Name'))
                ?? self::toString(Arr::get($data, 'ExceptionName'))
                ?? '',
            exceptionMessage: self::toString(Arr::get($exceptionData, 'Message'))
                ?? self::toString(Arr::get($data, 'Message'))
                ?? '',
            stack: self::toString(Arr::get($data, 'Stack') ?? Arr::get($exceptionData, 'Stack')),
        );
    }

    /**
     * Coerce a loosely-typed payload value into a string, or null when absent.
     */
    private static function toString(mixed $value): ?string
    {
        return match (true) {
            is_string($value) => $value,
            is_bool($value) => $value ? 'true' : 'false',
            is_int($value), is_float($value) => (string) $value,
            // `Stack` arrives as a list of frames rather than a string on some vaults.
            is_array($value) => self::stringifyList($value),
            default => null,
        };
    }

    /**
     * @param  array<array-key, mixed>  $value
     */
    private static function stringifyList(array $value): ?string
    {
        if ($value === []) {
            return null;
        }

        $scalars = array_filter($value, static fn (mixed $item): bool => is_scalar($item));

        if (count($scalars) === count($value)) {
            return implode("\n", array_map(static fn (mixed $item): string => (string) $item, $scalars));
        }

        $encoded = json_encode($value);

        return $encoded === false ? null : $encoded;
    }

    private static function toInt(mixed $value): int
    {
        return match (true) {
            is_int($value) => $value,
            is_float($value) => (int) $value,
            is_string($value) && is_numeric($value) => (int) $value,
            default => 0,
        };
    }

    /**
     * @return array<string, mixed>
     */
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
