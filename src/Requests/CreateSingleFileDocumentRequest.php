<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Requests;

use CodebarAg\MFiles\DTO\ObjectProperties;
use CodebarAg\MFiles\DTO\SetProperty;
use CodebarAg\MFiles\Responses\ObjectPropertiesResponse;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;
use Saloon\Traits\Plugins\AcceptsJson;

class CreateSingleFileDocumentRequest extends Request implements HasBody
{
    use AcceptsJson;
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param  array<string, mixed>|list<array<string, mixed>>  $files  A single upload-info
     *                                                                  array, or a list of them.
     * @param  list<SetProperty>  $propertyValues
     */
    public function __construct(
        public string $title,
        public array $files = [],
        public array $propertyValues = [],
    ) {}

    public function resolveEndpoint(): string
    {
        return '/objects/0';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return [
            'PropertyValues' => collect($this->propertyValues)
                ->map(fn (SetProperty $propertyValue) => $propertyValue->toArray())
                ->values()
                ->toArray(),
            'Files' => $this->normalisedFiles(),
        ];
    }

    /**
     * Normalise `$files` into the list M-Files expects.
     *
     * The body used to wrap `$files` unconditionally in another array. That only
     * produced valid JSON when a *single* upload-info array was passed, so the
     * documented `files: [$uploadedFile]` form sent a nested `[[{…}]]` that M-Files
     * rejects. Both shapes are accepted now, which also makes multi-file documents
     * possible without changing the signature.
     *
     * @return list<array<string, mixed>>
     */
    private function normalisedFiles(): array
    {
        if ($this->files === []) {
            return [];
        }

        // An M-Files upload-info array is associative (UploadID, Title, Extension…),
        // so a list can only be a collection of them.
        $files = array_is_list($this->files) ? $this->files : [$this->files];

        return array_values(array_filter($files, 'is_array'));
    }

    public function createDtoFromResponse(Response $response): ObjectProperties
    {
        return ObjectPropertiesResponse::createDtoFromResponse($response);
    }
}
