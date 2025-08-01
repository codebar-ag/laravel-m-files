<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Fixtures;

use Saloon\Http\Faking\Fixture;

class GetObjectInformationFixture extends Fixture
{
    protected function defineName(): string
    {
        return 'object-properties';
    }

    protected function defineSensitiveHeaders(): array
    {
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
        return [
            '/Fehlerreferenz-ID: [a-f0-9-]{36}/' => 'Fehlerreferenz-ID: REDACTED-ERROR-ID',
        ];
    }
}
