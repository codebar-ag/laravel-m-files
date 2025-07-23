<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Fixtures;

use Saloon\Http\Faking\Fixture;

class CurrentUserFixture extends Fixture
{
    protected function defineName(): string
    {
        return 'current-user';
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
        if (!env('SALOON_FIXTURE_REDACTION', true)) {
            return [];
        }

        return [
            'AccountName' => 'redacted@example.com',
            'SerialNumber' => 'REDACTED-SERIAL',
            'FullName' => 'REDACTED USER',
        ];
    }

    protected function defineSensitiveRegexPatterns(): array
    {
        return [];
    }
}
