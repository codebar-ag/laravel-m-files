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
        return [];
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
