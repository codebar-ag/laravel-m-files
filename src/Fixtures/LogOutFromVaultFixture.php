<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Fixtures;

use Saloon\Http\Faking\Fixture;

class LogOutFromVaultFixture extends Fixture
{
    protected function defineName(): string
    {
        return 'logout-from-vault';
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
        return [];
    }
}
