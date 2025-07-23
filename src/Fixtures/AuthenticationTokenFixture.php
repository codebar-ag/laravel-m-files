<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Fixtures;

use Saloon\Http\Faking\Fixture;

class AuthenticationTokenFixture extends Fixture
{
    protected function defineName(): string
    {
        return 'authentication-token';
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
            'Value' => 'REDACTED-AUTH-TOKEN',
        ];
    }

    protected function defineSensitiveRegexPatterns(): array
    {
        if (! env('SALOON_FIXTURE_REDACTION', true)) {
            return [];
        }

        return [
            '/[A-Za-z0-9_-]{100,}/' => 'REDACTED-AUTH-TOKEN',
        ];
    }
}
