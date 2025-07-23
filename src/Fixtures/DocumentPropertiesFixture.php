<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Fixtures;

use Saloon\Http\Faking\Fixture;

class DocumentPropertiesFixture extends Fixture
{
    protected function defineName(): string
    {
        return 'document-properties';
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
            '/Fehlerreferenz-ID: [a-f0-9-]{36}/' => 'Fehlerreferenz-ID: REDACTED-ERROR-ID',
        ];
    }
}
