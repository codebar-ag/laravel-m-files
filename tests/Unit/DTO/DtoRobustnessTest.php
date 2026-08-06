<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use CodebarAg\MFiles\DTO\File;
use CodebarAg\MFiles\DTO\GetProperty;
use CodebarAg\MFiles\DTO\ObjectProperties;
use CodebarAg\MFiles\Enums\MFDataTypeEnum;

/**
 * The skeleton of a real GET /objects/{type}/{id}/{version} payload, minus the
 * timestamps so each test can supply exactly the ones it is about.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function objectPayload(array $overrides = []): array
{
    return array_merge([
        'Title' => 'Lohnausweis 2024',
        'DisplayID' => '1090',
        'ObjVer' => [
            'Version' => 10,
            'VersionType' => 4,
            'ID' => 1090,
            'Type' => 0,
        ],
        'Class' => 158,
        'Properties' => [],
        'Files' => [],
    ], $overrides);
}

afterEach(function () {
    CarbonImmutable::setTestNow();
});

describe('ObjectProperties::fromArray timestamps', function () {
    test('reads LastModifiedUtc', function () {
        // ObjVer only ever carries {Version, VersionType, ID, Type}. Reading
        // "ObjVer.Modified" therefore always yielded null, and CarbonImmutable::parse(null)
        // silently returns "now" — so every object reported the moment it was fetched
        // as its modification date, and change detection never fired.
        CarbonImmutable::setTestNow('2030-01-01T00:00:00Z');

        $object = ObjectProperties::fromArray(objectPayload([
            'LastModifiedUtc' => '2025-08-01T13:29:39Z',
            'LastModified' => '2025-08-01T13:29:39Z',
            'CreatedUtc' => '2025-07-30T13:01:14Z',
            'Created' => '2025-07-30T13:01:14Z',
        ]));

        expect($object->lastModifiedAt->toISOString())->toBe('2025-08-01T13:29:39.000000Z')
            ->and($object->lastModifiedAt->toISOString())->not->toBe(CarbonImmutable::now()->toISOString());
    });

    test('prefers LastModifiedUtc over the local LastModified', function () {
        // The two differ on a vault that is not running in UTC; the UTC field is the
        // unambiguous one.
        $object = ObjectProperties::fromArray(objectPayload([
            'LastModifiedUtc' => '2025-08-01T13:29:39Z',
            'LastModified' => '2025-08-01T15:29:39Z',
        ]));

        expect($object->lastModifiedAt->toISOString())->toBe('2025-08-01T13:29:39.000000Z');
    });

    test('falls back to LastModified when the UTC variant is absent', function () {
        $object = ObjectProperties::fromArray(objectPayload([
            'LastModified' => '2025-08-01T13:29:39Z',
        ]));

        expect($object->lastModifiedAt->toISOString())->toBe('2025-08-01T13:29:39.000000Z');
    });

    test('still honours a legacy nested ObjVer.Modified', function () {
        // Kept working for any vault or fixture that does expose it.
        $object = ObjectProperties::fromArray(objectPayload([
            'ObjVer' => [
                'Version' => 10,
                'VersionType' => 4,
                'ID' => 1090,
                'Type' => 0,
                'Modified' => '2023-01-02T11:00:00Z',
            ],
        ]));

        expect($object->lastModifiedAt->toISOString())->toBe('2023-01-02T11:00:00.000000Z');
    });

    test('falls back to CreatedUtc for an object that was never modified', function () {
        $object = ObjectProperties::fromArray(objectPayload([
            'CreatedUtc' => '2025-07-30T13:01:14Z',
            'Created' => '2025-07-30T13:01:14Z',
        ]));

        expect($object->lastModifiedAt->toISOString())->toBe('2025-07-30T13:01:14.000000Z');
    });

    test('falls back to Created as the last resort', function () {
        $object = ObjectProperties::fromArray(objectPayload([
            'Created' => '2025-07-30T13:01:14Z',
        ]));

        expect($object->lastModifiedAt->toISOString())->toBe('2025-07-30T13:01:14.000000Z');
    });

    test('skips an unparseable timestamp instead of throwing', function () {
        // A vault behind a locale-mangling proxy can hand back something Carbon cannot
        // read. That must not abort the whole object.
        $object = ObjectProperties::fromArray(objectPayload([
            'LastModifiedUtc' => 'not-a-real-timestamp',
            'CreatedUtc' => '2025-07-30T13:01:14Z',
        ]));

        expect($object->lastModifiedAt->toISOString())->toBe('2025-07-30T13:01:14.000000Z');
    });

    test('skips an empty timestamp', function () {
        $object = ObjectProperties::fromArray(objectPayload([
            'LastModifiedUtc' => '   ',
            'LastModified' => '2025-08-01T13:29:39Z',
        ]));

        expect($object->lastModifiedAt->toISOString())->toBe('2025-08-01T13:29:39.000000Z');
    });

    test('falls back to now when no candidate is usable', function () {
        CarbonImmutable::setTestNow('2030-01-01T00:00:00Z');

        $object = ObjectProperties::fromArray(objectPayload([
            'LastModifiedUtc' => 'nonsense',
            'LastModified' => null,
        ]));

        expect($object->lastModifiedAt->toISOString())->toBe('2030-01-01T00:00:00.000000Z');
    });
});

describe('ObjectProperties::fromArray required fields', function () {
    test('names the missing field when Class is absent', function () {
        // Previously a TypeError from the constructor, which said nothing about which
        // part of the payload was wrong.
        $payload = objectPayload(['LastModifiedUtc' => '2025-08-01T13:29:39Z']);
        unset($payload['Class']);

        expect(fn () => ObjectProperties::fromArray($payload))
            ->toThrow(InvalidArgumentException::class, '[Class]');
    });

    test('names the missing field when ObjVer.ID is absent', function () {
        $payload = objectPayload([
            'ObjVer' => ['Version' => 10, 'VersionType' => 4, 'Type' => 0],
            'LastModifiedUtc' => '2025-08-01T13:29:39Z',
        ]);

        expect(fn () => ObjectProperties::fromArray($payload))
            ->toThrow(InvalidArgumentException::class, '[ObjVer.ID]');
    });

    test('accepts numeric-string identifiers', function () {
        $object = ObjectProperties::fromArray(objectPayload([
            'Class' => '158',
            'ObjVer' => ['Version' => '10', 'VersionType' => 4, 'ID' => '1090', 'Type' => '0'],
            'LastModifiedUtc' => '2025-08-01T13:29:39Z',
        ]));

        expect($object->classId)->toBe(158)
            ->and($object->objectId)->toBe(1090)
            ->and($object->objectTypeId)->toBe(0)
            ->and($object->objectVersionId)->toBe(10);
    });
});

describe('ObjectProperties::fromArray collections', function () {
    test('skips non-array entries inside Properties and Files', function () {
        // array_map over the raw lists used to hand a string to a function typed
        // array and take down the entire response.
        $object = ObjectProperties::fromArray(objectPayload([
            'LastModifiedUtc' => '2025-08-01T13:29:39Z',
            'Properties' => [
                'unexpected string',
                null,
                [
                    'PropertyDef' => 0,
                    'Value' => ['Value' => 'Lohnausweis 2024', 'DisplayValue' => 'Lohnausweis 2024', 'DataType' => 1],
                ],
            ],
            'Files' => [
                'unexpected string',
                ['ID' => 1116, 'Name' => 'Lohnausweis 2024', 'Extension' => 'pdf', 'Version' => 1, 'Size' => 646466],
            ],
        ]));

        expect($object->properties)->toHaveCount(1)
            ->and($object->files)->toHaveCount(1)
            ->and($object->properties->first()?->propertyDef)->toBe(0)
            ->and($object->files->first()?->name)->toBe('Lohnausweis 2024');
    });

    test('treats a non-list Properties value as empty', function () {
        $object = ObjectProperties::fromArray(objectPayload([
            'LastModifiedUtc' => '2025-08-01T13:29:39Z',
            'Properties' => 'not a list',
            'Files' => 'not a list',
        ]));

        expect($object->properties)->toHaveCount(0)
            ->and($object->files)->toHaveCount(0);
    });

    test('reindexes the surviving entries', function () {
        // array_filter preserves keys, so the collection would otherwise be keyed
        // 1, 2, … and first()/get(0) would disagree.
        $object = ObjectProperties::fromArray(objectPayload([
            'LastModifiedUtc' => '2025-08-01T13:29:39Z',
            'Files' => [
                'skipped',
                ['ID' => 1116, 'Name' => 'first.pdf'],
                ['ID' => 1117, 'Name' => 'second.pdf'],
            ],
        ]));

        expect($object->files->keys()->all())->toBe([0, 1])
            ->and($object->files->get(0)?->id)->toBe(1116);
    });
});

describe('GetProperty::fromArray data types', function () {
    test('degrades an undocumented DataType to TEXT', function () {
        // MFDataTypeEnum has no case 4. tryFrom() returned null, which was passed
        // straight into a non-nullable enum parameter — one exotic property killed the
        // whole object response with a TypeError.
        $property = GetProperty::fromArray([
            'PropertyDef' => 1291,
            'Value' => [
                'Value' => 'something exotic',
                'HasValue' => true,
                'DisplayValue' => 'something exotic',
                'DataType' => 4,
            ],
        ]);

        expect($property->dataType)->toBe(MFDataTypeEnum::TEXT)
            ->and($property->value)->toBe('something exotic')
            ->and($property->displayValue)->toBe('something exotic')
            ->and($property->propertyDef)->toBe(1291);
    });

    test('degrades a far-future DataType to TEXT', function () {
        $property = GetProperty::fromArray([
            'PropertyDef' => 42,
            'Value' => ['Value' => 'x', 'DisplayValue' => 'x', 'DataType' => 99],
        ]);

        expect($property->dataType)->toBe(MFDataTypeEnum::TEXT)
            ->and($property->value)->toBe('x');
    });

    test('accepts a numeric-string DataType', function () {
        $property = GetProperty::fromArray([
            'PropertyDef' => '23',
            'Value' => [
                'Lookup' => ['Item' => 25, 'Version' => -1, 'DisplayValue' => 'Clouddocs', 'ObjectType' => 6],
                'HasValue' => true,
                'DisplayValue' => 'Clouddocs',
                'DataType' => '9',
            ],
        ]);

        expect($property->dataType)->toBe(MFDataTypeEnum::LOOKUP)
            ->and($property->propertyDef)->toBe(23)
            ->and($property->displayValue)->toBe('Clouddocs')
            ->and($property->value)->toBeArray();
    });

    test('falls back to TEXT when DataType is missing', function () {
        $property = GetProperty::fromArray([
            'PropertyDef' => 0,
            'Value' => ['Value' => 'Lohnausweis 2024', 'DisplayValue' => 'Lohnausweis 2024'],
        ]);

        expect($property->dataType)->toBe(MFDataTypeEnum::TEXT)
            ->and($property->value)->toBe('Lohnausweis 2024');
    });

    test('falls back to TEXT when the Value envelope is missing entirely', function () {
        $property = GetProperty::fromArray(['PropertyDef' => 0]);

        expect($property->dataType)->toBe(MFDataTypeEnum::TEXT)
            ->and($property->value)->toBeNull()
            ->and($property->displayValue)->toBeNull();
    });

    test('reads the Lookups list for a multi-select lookup', function () {
        $property = GetProperty::fromArray([
            'PropertyDef' => 101,
            'Value' => [
                'Lookups' => [['Item' => 14, 'DisplayValue' => 'LO - LOHN', 'ObjectType' => 2]],
                'HasValue' => true,
                'DisplayValue' => 'LO - LOHN',
                'DataType' => 10,
            ],
        ]);

        expect($property->dataType)->toBe(MFDataTypeEnum::MULTISELECTLOOKUP)
            ->and($property->value)->toBeArray()
            ->and($property->displayValue)->toBe('LO - LOHN');
    });

    test('names the missing field when PropertyDef is absent', function () {
        expect(fn () => GetProperty::fromArray([
            'Value' => ['Value' => 'x', 'DisplayValue' => 'x', 'DataType' => 1],
        ]))->toThrow(InvalidArgumentException::class, '[PropertyDef]');
    });
});

describe('File::fromArray', function () {
    test('reads the real M-Files file shape', function () {
        $file = File::fromArray([
            'Name' => 'Lohnausweis 2024',
            'EscapedName' => 'Lohnausweis 2024.pdf',
            'Extension' => 'pdf',
            'Size' => 646466,
            'ID' => 1116,
            'Version' => 1,
            'FileVersionType' => 3,
        ]);

        expect($file->id)->toBe(1116)
            ->and($file->name)->toBe('Lohnausweis 2024')
            ->and($file->extension)->toBe('pdf')
            ->and($file->version)->toBe(1)
            ->and($file->size)->toBe(646466);
    });

    test('names the missing field when ID is absent', function () {
        expect(fn () => File::fromArray(['Name' => 'Lohnausweis 2024', 'Extension' => 'pdf']))
            ->toThrow(InvalidArgumentException::class, '[ID]');
    });

    test('coerces numeric-string ID, Version and Size to integers', function () {
        // Some vault versions serialise 64-bit sizes as strings; the int type hints
        // then raised a TypeError under strict_types.
        $file = File::fromArray([
            'ID' => '1116',
            'Name' => 'Lohnausweis 2024',
            'Extension' => 'pdf',
            'Version' => '1',
            'Size' => '646466',
        ]);

        expect($file->id)->toBe(1116)
            ->and($file->version)->toBe(1)
            ->and($file->size)->toBe(646466);
    });

    test('substitutes an empty name rather than failing', function () {
        // A file whose name the vault omits is still downloadable by ID, so losing the
        // whole object over it would be the worse outcome.
        $file = File::fromArray(['ID' => 1116, 'Extension' => 'pdf']);

        expect($file->name)->toBe('')
            ->and($file->id)->toBe(1116);
    });

    test('leaves Extension, Version and Size null when absent', function () {
        $file = File::fromArray(['ID' => 1116, 'Name' => 'CHANGELOG']);

        expect($file->extension)->toBeNull()
            ->and($file->version)->toBeNull()
            ->and($file->size)->toBeNull();
    });

    test('leaves a non-numeric Size null rather than casting it to zero', function () {
        // Reporting 0 bytes would look like a legitimately empty file.
        $file = File::fromArray(['ID' => 1116, 'Name' => 'report', 'Size' => 'unknown']);

        expect($file->size)->toBeNull();
    });
});
