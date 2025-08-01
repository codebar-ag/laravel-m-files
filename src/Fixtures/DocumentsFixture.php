<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Fixtures;

use Saloon\Http\Faking\Fixture;

class DocumentsFixture extends Fixture
{
    protected function defineName(): string
    {
        return 'documents';
    }

    protected function defineSensitiveHeaders(): array
    {
        if (! env('SALOON_FIXTURE_REDACTION', true)) {
            return [];
        }

        return [
            'Set-Cookie' => 'REDACTED',
        ];
    }

    protected function defineSensitiveJsonParameters(): array
    {
        if (! env('SALOON_FIXTURE_REDACTION', true)) {
            return [];
        }

        return [
            'ObjectGUID' => 'REDACTED-GUID',
        ];
    }

    protected function defineSensitiveRegexPatterns(): array
    {
        if (! env('SALOON_FIXTURE_REDACTION', true)) {
            return [];
        }

        return [
            '/\{[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}\}/i' => '{REDACTED-GUID}',
            '/2025-07-1[0-9]T[0-9]{2}:[0-9]{2}:[0-9]{2}Z/' => '2024-01-01T00:00:00Z',
            '/[0-9]{2}\.07\.2025 [0-9]{2}:[0-9]{2}/' => '01.01.2024 00:00',
        ];
    }
}
