<img src="https://banners.beyondco.de/Laravel%20M-Files.png?theme=light&packageManager=composer+require&packageName=codebar-ag%2Flaravel-m-files&pattern=circuitBoard&style=style_1&description=An+opinionated+way+to+integrate+M-Files+with+Laravel&md=1&showWatermark=0&fontSize=175px&images=document-report" alt="Laravel M-Files banner with description and logo">

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codebar-ag/laravel-m-files.svg?style=flat-square)](https://packagist.org/packages/codebar-ag/laravel-m-files)
[![GitHub-Tests](https://github.com/codebar-ag/laravel-m-files/actions/workflows/run-tests.yml/badge.svg?branch=main)](https://github.com/codebar-ag/laravel-m-files/actions/workflows/run-tests.yml)
[![GitHub Code Style](https://github.com/codebar-ag/laravel-m-files/actions/workflows/fix-php-code-style-issues.yml/badge.svg?branch=main)](https://github.com/codebar-ag/laravel-m-files/actions/workflows/fix-php-code-style-issues.yml)
[![Larastan](https://img.shields.io/badge/Larastan-Level%205-brightgreen.svg)](https://github.com/larastan/larastan)
[![Total Downloads](https://img.shields.io/packagist/dt/codebar-ag/laravel-m-files.svg?style=flat-square)](https://packagist.org/packages/codebar-ag/laravel-m-files)

# Laravel M-Files Integration

A Laravel package providing DTOs and requests for integrating with M-Files REST API.

## Installation

You can install the package via composer:

```bash
composer require codebar-ag/laravel-m-files
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="CodebarAg\MFiles\MFilesServiceProvider"
```

Add your M-Files authentication credentials to your `.env` file:

```env
M_FILES_URL=https://your-mfiles-server.com
M_FILES_USERNAME=your-username
M_FILES_PASSWORD=your-password
M_FILES_VAULT_GUID=ABC0DE2G-3HW-QWCQ-SDF3-WERWETWETW
M_FILES_CACHE_DRIVER=file
```

### Configuration Options

The package supports the following configuration options:

- `M_FILES_URL` - Your M-Files server URL
- `M_FILES_USERNAME` - Your M-Files username
- `M_FILES_PASSWORD` - Your M-Files password
- `M_FILES_VAULT_GUID` - The vault GUID to connect to
- `M_FILES_CACHE_DRIVER` - Cache driver for storing authentication tokens (default: file)

## Authentication

The package provides automatic authentication token management with caching support.

### M-Files Connector

```php
use CodebarAg\MFiles\Connectors\MFilesConnector;
use CodebarAg\MFiles\DTO\ConfigWithCredentials;

$config = new ConfigWithCredentials(
    url: 'https://your-mfiles-server.com',
    vaultGuid: '{ABC0DE2G-3HW-QWCQ-SDF3-WERWETWETW}',
    username: 'your-username',
    password: 'your-password',
    cacheDriver: 'file'
);

$connector = new MFilesConnector(config: $config);
```

### Authentication

Authentication is handled automatically by the `MFilesConnector`. When you create a connector instance with your credentials, it will automatically:

1. **Cache authentication tokens** - Tokens are cached for 1 hour to avoid repeated login requests
2. **Include authentication headers** - The `X-Authentication` header is automatically added to all requests
3. **Handle token refresh** - When tokens expire, new ones are automatically obtained

```php
use CodebarAg\MFiles\Requests\LogInToVaultRequest;

// Manual authentication (if needed)
$request = new LogInToVaultRequest(
    url: 'https://your-mfiles-server.com',
    vaultGuid: '{ABC0DE2G-3HW-QWCQ-SDF3-WERWETWETW}',
    username: 'your-username',
    password: 'your-password',
);

$token = $request->send()->dto();
// Returns authentication token as string
```

## Requests

### Authentication Requests

#### LogInToVaultRequest

Gets an authentication token using username/password credentials.

**Constructor Parameters:**
- `url` (string) - M-Files server URL
- `vaultGuid` (string) - Vault GUID
- `username` (string) - M-Files username
- `password` (string) - M-Files password

**Request:**
```php
use CodebarAg\MFiles\Requests\LogInToVaultRequest;

$request = new LogInToVaultRequest(
    url: 'https://your-mfiles-server.com',
    vaultGuid: '{ABC0DE2G-3HW-QWCQ-SDF3-WERWETWETW}',
    username: 'your-username',
    password: 'your-password',
);
```

**Response:**
```php
$token = $request->send()->dto();
// Returns authentication token as string
```

### File Requests

#### UploadFileRequest

Uploads a file to M-Files.

**Constructor Parameters:**
- `fileContent` (string) - File content
- `fileName` (string) - File name

**Request:**
```php
use CodebarAg\MFiles\Requests\UploadFileRequest;

$request = new UploadFileRequest(
    fileContent: $fileContent,
    fileName: 'document.pdf'
);
```

**Response:**
```php
$uploadedFile = $connector->send($request)->dto();
// Returns array with file information including Title, Extension, and other metadata
```

#### CreateSingleFileDocumentRequest

Creates a single file document in M-Files.

**Constructor Parameters:**
- `title` (string) - Document title
- `files` (array) - Array of uploaded file information
- `propertyValues` (array) - Array of SetProperty objects for custom properties

**Request:**
```php
use CodebarAg\MFiles\Requests\CreateSingleFileDocumentRequest;
use CodebarAg\MFiles\DTO\SetProperty;
use CodebarAg\MFiles\Enums\MFDataTypeEnum;

$request = new CreateSingleFileDocumentRequest(
    title: 'My Document',
    files: [$uploadedFile]
);
```

**Response:**
```php
use CodebarAg\MFiles\DTO\ObjectProperties;

$document = $connector->send($request)->dto();
// Returns ObjectProperties DTO with document information
```

**With Custom Property Values:**
```php
$propertyValues = [
    new SetProperty(propertyDef: 0, dataType: MFDataTypeEnum::TEXT, value: 'Custom Title'),
    new SetProperty(propertyDef: 5, dataType: MFDataTypeEnum::DATE, value: '2024-01-01'),
];

$request = new CreateSingleFileDocumentRequest(
    title: 'Custom Document',
    files: [$uploadedFile],
    propertyValues: $propertyValues
);
```

#### DownloadFileRequest

Downloads a file from M-Files.

**Constructor Parameters:**
- `objectType` (int) - Object type ID
- `objectId` (int) - Object ID
- `objectVersion` (int) - Object version
- `fileId` (int) - File ID

**Request:**
```php
use CodebarAg\MFiles\Requests\DownloadFileRequest;

$request = new DownloadFileRequest(
    objectType: 0,
    objectId: 123,
    objectVersion: 1,
    fileId: 456
);
```

**Response:**
```php
use CodebarAg\MFiles\DTO\DownloadedFile;

$downloadedFile = $connector->send($request)->dto();
// Returns DownloadedFile DTO with content, name, extension, size, contentType
```

## DTOs

### Configuration DTOs

#### ConfigWithCredentials

Represents M-Files configuration with authentication credentials.

**Properties:**
- `url` (string) - M-Files server URL
- `vaultGuid` (string) - Vault GUID
- `username` (string) - M-Files username
- `password` (string) - M-Files password
- `cacheDriver` (string|null) - Cache driver for tokens

**Methods:**
- `fromArray(array $data): self` - Static factory method
- `toArray(): array` - Converts to array format

**Usage:**
```php
use CodebarAg\MFiles\DTO\ConfigWithCredentials;

$config = new ConfigWithCredentials(
    url: 'https://your-mfiles-server.com',
    username: 'your-username',
    password: 'your-password',
    vaultGuid: '{ABC0DE2G-3HW-QWCQ-SDF3-WERWETWETW}',
    cacheDriver: 'file'
);

// Using static factory method
$config = ConfigWithCredentials::fromArray([
    'url' => 'https://your-mfiles-server.com',
    'username' => 'your-username',
    'password' => 'your-password',
    'vaultGuid' => '{ABC0DE2G-3HW-QWCQ-SDF3-WERWETWETW}',
    'cacheDriver' => 'file'
]);
```

### File DTOs

#### File

Represents a file in M-Files.

**Properties:**
- `id` (int) - File ID
- `name` (string) - File name
- `extension` (string|null) - File extension
- `version` (int|null) - File version
- `size` (int|null) - File size in bytes

**Methods:**
- `fromArray(array $data): self` - Static factory method
- `toArray(): array` - Converts to array format

**Usage:**
```php
use CodebarAg\MFiles\DTO\File;

$file = new File(
    id: 456,
    name: 'document.pdf',
    extension: 'pdf',
    version: 1,
    size: 1024
);

// Using static factory method
$file = File::fromArray([
    'ID' => 456,
    'Name' => 'document.pdf',
    'Extension' => 'pdf',
    'Version' => 1,
    'Size' => 1024
]);
```

#### DownloadedFile

Represents a downloaded file with content and metadata.

**Properties:**
- `name` (string|null) - File name
- `extension` (string|null) - File extension
- `size` (int|null) - File size in bytes
- `contentType` (string|null) - MIME content type
- `content` (string) - File content

**Methods:**
- `fromArray(array $data): self` - Static factory method
- `toArray(): array` - Converts to array format

**Usage:**
```php
use CodebarAg\MFiles\DTO\DownloadedFile;

$downloadedFile = new DownloadedFile(
    name: 'document.pdf',
    extension: 'pdf',
    size: 1024,
    contentType: 'application/pdf',
    content: $fileContent
);

// Using static factory method
$downloadedFile = DownloadedFile::fromArray([
    'name' => 'document.pdf',
    'extension' => 'pdf',
    'size' => 1024,
    'contentType' => 'application/pdf',
    'content' => $fileContent
]);
```

### Property DTOs

#### SetProperty

Represents a property value for creating documents.

**Properties:**
- `propertyDef` (int) - Property definition ID
- `dataType` (MFDataTypeEnum) - Property data type
- `value` (mixed) - Property value
- `displayValue` (mixed) - Display value (optional)

**Methods:**
- `fromArray(int $propertyDef, MFDataTypeEnum $dataType, mixed $value, mixed $displayValue = null): self` - Static factory method
- `toArray(): array` - Converts to array format for API requests

**Usage:**
```php
use CodebarAg\MFiles\DTO\SetProperty;
use CodebarAg\MFiles\Enums\MFDataTypeEnum;

$propertyValue = new SetProperty(
    propertyDef: 0,
    dataType: MFDataTypeEnum::TEXT,
    value: 'Sample Text'
);

// Using static factory method
$propertyValue = SetProperty::fromArray(
    propertyDef: 0,
    dataType: MFDataTypeEnum::TEXT,
    value: 'Sample Text'
);

// Convert to array for API requests
$array = $propertyValue->toArray();
```

#### GetProperty

Represents a property retrieved from M-Files.

**Properties:**
- `propertyDef` (int) - Property definition ID
- `dataType` (MFDataTypeEnum) - Property data type
- `value` (mixed) - Property value
- `displayValue` (mixed) - Display value

**Methods:**
- `fromArray(array $data): self` - Static factory method
- `toArray(): array` - Converts to array format

**Usage:**
```php
use CodebarAg\MFiles\DTO\GetProperty;

$property = GetProperty::fromArray([
    'PropertyDef' => 0,
    'Value' => [
        'DataType' => 1,
        'Value' => 'Sample Text',
        'DisplayValue' => 'Sample Text'
    ]
]);
```

#### ObjectProperties

Represents object properties in M-Files.

**Properties:**
- `classId` (int) - Class ID
- `objectId` (int) - Object ID
- `objectTypeId` (int) - Object type ID
- `objectVersionId` (int) - Object version ID
- `lastModifiedAt` (CarbonImmutable) - Last modified timestamp
- `properties` (Collection) - Collection of GetProperty objects
- `files` (Collection) - Collection of File objects

**Methods:**
- `fromArray(array $data): self` - Static factory method
- `toArray(): array` - Converts to array format

**Usage:**
```php
use CodebarAg\MFiles\DTO\ObjectProperties;

$objectProperties = ObjectProperties::fromArray([
    'Class' => 1,
    'ObjVer' => [
        'ID' => 123,
        'Type' => 0,
        'Version' => 1,
        'Modified' => '2024-01-01T00:00:00Z'
    ],
    'Properties' => [],
    'Files' => []
]);
```

### Enums

#### MFDataTypeEnum

Represents data types in M-Files.

**Available Values:**
- `UNINITIALIZED` (0) - Document/Object
- `TEXT` (1) - Text
- `INTEGER` (2) - A 32-bit integer
- `FLOATING` (3) - A double-precision floating point
- `DATE` (5) - Date
- `TIME` (6) - Time
- `TIMESTAMP` (7) - Timestamp
- `BOOLEAN` (8) - Boolean
- `LOOKUP` (9) - Lookup (from a value list)
- `MULTISELECTLOOKUP` (10) - Multiple selection from a value list
- `INTEGER64` (11) - A 64-bit integer
- `FILETIME` (12) - FILETIME (a 64-bit integer)
- `MULTILINETEXT` (13) - Multi-line text
- `ACL` (14) - The access control list (ACL)

**Usage:**
```php
use CodebarAg\MFiles\Enums\MFDataTypeEnum;

$dataType = MFDataTypeEnum::TEXT;
$dataTypeValue = $dataType->value; // 1
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email security@codebar.ch instead of using the issue tracker.

## Credits

- [Codebar Solutions AG](https://github.com/codebar-ag)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
