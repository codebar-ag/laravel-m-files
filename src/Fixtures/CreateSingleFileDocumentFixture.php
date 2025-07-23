<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Fixtures;

use Saloon\Http\Faking\Fixture;

class CreateSingleFileDocumentFixture extends Fixture
{
    protected function defineName(): string
    {
        return 'create-single-file-document';
    }

    protected function defineSensitiveHeaders(): array
    {
        if (!env('SALOON_FIXTURE_REDACTION', true)) {
            return [];
        }

        return [
            'Set-Cookie' => 'REDACTED',
        ];
    }

    protected function defineSensitiveJsonParameters(): array
    {
        return [];
    }

    protected function defineSensitiveRegexPatterns(): array
    {
        if (!env('SALOON_FIXTURE_REDACTION', true)) {
            return [];
        }

        return [
            '/2025-07-1[0-9]T[0-9]{2}:[0-9]{2}:[0-9]{2}Z/' => '2024-01-01T00:00:00Z',
        ];
    }
}
