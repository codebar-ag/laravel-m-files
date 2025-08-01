<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Fixtures;

use Saloon\Http\Faking\Fixture;

class LogInToVaultFixture extends Fixture
{
    protected function defineName(): string
    {
        return 'login-to-vault';
    }

    protected function defineSensitiveHeaders(): array
    {
        return [
            'Set-Cookie' => 'REDACTED',
        ];
    }

    protected function defineSensitiveJsonParameters(): array
    {
        return [
            'Value' => 'REDACTED-AUTH-TOKEN',
        ];
    }

    protected function defineSensitiveRegexPatterns(): array
    {
        return [
            '/[A-Za-z0-9_-]{100,}/' => 'REDACTED-AUTH-TOKEN',
        ];
    }
}
