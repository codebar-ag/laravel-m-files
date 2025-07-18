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
        return [
            // 'Set-Cookie' => 'REDACTED',
        ];
    }

    protected function defineSensitiveJsonParameters(): array
    {
        return [
            // 'AccountName' => 'redacted@example.com',
            // 'SerialNumber' => 'REDACTED-SERIAL',
            // 'FullName' => 'REDACTED USER',
        ];
    }

    protected function defineSensitiveRegexPatterns(): array
    {
        return [];
    }
}
