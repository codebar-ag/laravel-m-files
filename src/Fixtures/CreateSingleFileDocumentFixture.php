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
