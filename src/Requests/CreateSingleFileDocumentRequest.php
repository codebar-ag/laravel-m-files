<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Requests;

use CodebarAg\MFiles\DTO\Document;
use CodebarAg\MFiles\DTO\PropertyValue;
use CodebarAg\MFiles\Enums\MFDataTypeEnum;
use CodebarAg\MFiles\Helpers\TypedPropertyBuilder;
use CodebarAg\MFiles\Responses\CreateSingleFileDocumentResponse;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;
use Saloon\Traits\Plugins\AcceptsJson;

class CreateSingleFileDocumentRequest extends Request implements HasBody
{
    use AcceptsJson;
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        public string $title,
        public array $file,
        public ?array $propertyValues = null,
        public ?int $objectTypeId = null,
    ) {
        if (empty($this->title)) {
            throw new \InvalidArgumentException('Title is required');
        }

        if (empty($this->file)) {
            throw new \InvalidArgumentException('File data is required');
        }

        if ($this->propertyValues !== null) {
            $this->propertyValues = array_map(
                fn ($propertyValue) => $propertyValue instanceof PropertyValue ? $propertyValue->toArray() : $propertyValue,
                $this->propertyValues
            );
        }
    }

    public function resolveEndpoint(): string
    {
        return '/objects/0';
    }

    protected function defaultBody(): array
    {
        $body = [];

        if ($this->propertyValues !== null) {
            $body['PropertyValues'] = $this->propertyValues;
        } else {
            $body['PropertyValues'] = $this->getDefaultPropertyValues();
        }

        $body['Files'] = [$this->file];

        return $body;
    }

    protected function getDefaultPropertyValues(): array
    {
        return [
            TypedPropertyBuilder::buildTypedProperty(0, MFDataTypeEnum::TEXT, $this->title),
            TypedPropertyBuilder::buildTypedProperty(22, MFDataTypeEnum::BOOLEAN, true),
        ];
    }

    public function createDtoFromResponse(\Saloon\Http\Response $response): Document
    {
        return CreateSingleFileDocumentResponse::createDtoFromResponse($response);
    }
}
