<img src="https://banners.beyondco.de/Laravel%20M-Files.png?theme=light&packageManager=composer+require&packageName=codebar-ag%2Flaravel-m-files&pattern=circuitBoard&style=style_1&description=An+opinionated+way+to+integrate+M-Files+with+Laravel&md=1&showWatermark=0&fontSize=175px&images=document-report" alt="Laravel M-Files banner with description and logo">

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codebar-ag/laravel-m-files.svg?style=flat-square)](https://packagist.org/packages/codebar-ag/laravel-m-files)
[![GitHub-Tests](https://github.com/codebar-ag/laravel-m-files/actions/workflows/run-tests.yml/badge.svg?branch=main)](https://github.com/codebar-ag/laravel-m-files/actions/workflows/run-tests.yml)
[![GitHub Code Style](https://github.com/codebar-ag/laravel-m-files/actions/workflows/fix-php-code-style-issues.yml/badge.svg?branch=main)](https://github.com/codebar-ag/laravel-m-files/actions/workflows/fix-php-code-style-issues.yml)
[![Larastan](https://img.shields.io/badge/Larastan-Level%208-brightgreen.svg)](https://github.com/larastan/larastan)
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

### Basic Authentication Setup

```php
use CodebarAg\MFiles\Connectors\MFilesConnector;
use CodebarAg\MFiles\DTO\Config\ConfigWithCredentials;

$config = new ConfigWithCredentials();

$connector = new MFilesConnector(config: $config);
```

### Authentication

```php
use CodebarAg\MFiles\Requests\LogInToVaultRequest;
use CodebarAg\MFiles\DTO\AuthenticationToken;

$request = new LogInToVaultRequest(
    url: 'https://your-mfiles-server.com',
    vaultGuid: '{ABC0DE2G-3HW-QWCQ-SDF3-WERWETWETW}',
    username: 'your-username',
    password: 'your-password',
    cacheDriver: 'file',
);

$token = $request->send()->dto();
// Returns AuthenticationToken with sessionId
```

### Logout

```php
use CodebarAg\MFiles\Requests\Authentication\LogOutFromVaultRequest;

$logout = (new LogOutFromVaultRequest(config: $config))->send()->dto();
// Returns true on successful logout, clears cached token
```

## Requests

### Authentication Requests

#### LogInToVaultRequest

Gets an authentication token using username/password credentials.

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
use CodebarAg\MFiles\DTO\AuthenticationToken;

$token = $request->send()->dto();
// Returns AuthenticationToken with sessionId
```

#### LogOutFromVaultRequest

Logs out from the vault and clears the cached authentication token.

**Request:**
```php
use CodebarAg\MFiles\Requests\LogOutFromVaultRequest;

$request = new LogOutFromVaultRequest(config: $config);
```

**Response:**
```php
$result = $request->send()->dto();
// Returns true on successful logout
```

### Vault Requests

#### GetVaultsRequest

Gets the list of available vaults for the authenticated user.

**Request:**
```php
use CodebarAg\MFiles\Requests\GetVaultsRequest;

$request = new GetVaultsRequest();
```

**Response:**
```php
$vaults = $connector->send($request)->json();
// Returns array of available vaults
```

### Object Requests

#### GetObjectInformationRequest

Gets object properties for documents, folders, and other object types.

**Request:**
```php
use CodebarAg\MFiles\Requests\GetObjectInformationRequest;

$request = new GetObjectInformationRequest(
    objectType: 0, // 0 for documents, 1 for folders, etc.
    objectId: 123
);
```

**Response:**
```php
use CodebarAg\MFiles\DTO\ObjectProperties;

$properties = $connector->send($request)->dto();
// Returns ObjectProperties with property details
```

**With Optional Parameters:**
```php
$request = new GetObjectInformationRequest(
    objectType: 0,
    objectId: 123,
    includeDeleted: false
);
```

### File Requests

#### UploadFileRequest

Uploads a file to M-Files.

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
// Returns array with file ID, title, extension
```

#### CreateSingleFileDocumentRequest

Creates a single file document in M-Files.

**Request:**
```php
use CodebarAg\MFiles\Requests\CreateSingleFileDocumentRequest;
use CodebarAg\MFiles\DTO\PropertyValue;
use CodebarAg\MFiles\Enums\MFDataTypeEnum;

$request = new CreateSingleFileDocumentRequest(
    title: 'My Document',
    file: $uploadedFile
);
```

**Response:**
```php
use CodebarAg\MFiles\DTO\Document;

$document = $connector->send($request)->dto();
// Returns Document DTO with id, title, files, etc.
```

**With Custom Property Values:**
```php
$propertyValues = [
    new PropertyValue(propertyDef: 0, dataType: MFDataTypeEnum::TEXT, value: 'Custom Title'),
    new PropertyValue(propertyDef: 5, dataType: MFDataTypeEnum::DATE, value: '2024-01-01'),
];

$request = new CreateSingleFileDocumentRequest(
    title: 'Custom Document',
    file: $uploadedFile,
    propertyValues: $propertyValues
);
```

#### DownloadFileRequest

Downloads a file from M-Files.

**Request:**
```php
use CodebarAg\MFiles\Requests\DownloadFileRequest;

$request = new DownloadFileRequest(
    objectId: 123,
    fileId: 456
);
```

**Response:**
```php
use CodebarAg\MFiles\DTO\DownloadedFile;

$downloadedFile = $connector->send($request)->dto();
// Returns DownloadedFile with content, name, extension, size, contentType
```

**With Optional Parameters:**
```php
$request = new DownloadFileRequest(
    objectId: 123,
    fileId: 456,
    objectTypeId: 0,
    includeDeleted: false
);
```

## DTOs

### Authentication DTOs

#### AuthenticationToken

Represents an M-Files authentication token.

**Properties:**
- `sessionId` (string) - The session ID for the authenticated session

**Usage:**
```php
use CodebarAg\MFiles\DTO\AuthenticationToken;

$token = new AuthenticationToken(sessionId: 'abc123');
```

### Configuration DTOs

#### ConfigWithCredentials

Represents M-Files configuration with authentication credentials.

**Properties:**
- `url` (string) - M-Files server URL
- `vaultGuid` (string) - Vault GUID
- `username` (string) - M-Files username
- `password` (string) - M-Files password
- `cacheDriver` (string) - Cache driver for tokens

**Usage:**
```php
use CodebarAg\MFiles\DTO\Config\ConfigWithCredentials;

$config = new ConfigWithCredentials(
    url: 'https://your-mfiles-server.com',
    username: 'your-username',
    password: 'your-password',
    vaultGuid: '{ABC0DE2G-3HW-QWCQ-SDF3-WERWETWETW}',
    cacheDriver: 'file'
);
```

### Document DTOs

#### Document

Represents a document in M-Files.

**Properties:**
- `id` (int) - Document ID
- `title` (string) - Document title
- `files` (array) - Array of file information
- `properties` (array) - Document properties

**Usage:**
```php
use CodebarAg\MFiles\DTO\Document;

$document = new Document(
    id: 123,
    title: 'My Document',
    files: [],
    properties: []
);
```

#### File

Represents a file in M-Files.

**Properties:**
- `id` (int) - File ID
- `title` (string) - File title
- `extension` (string) - File extension

**Usage:**
```php
use CodebarAg\MFiles\DTO\File;

$file = new File(
    id: 456,
    title: 'document.pdf',
    extension: 'pdf'
);
```

#### DownloadedFile

Represents a downloaded file with content and metadata.

**Properties:**
- `content` (string) - File content
- `name` (string) - File name
- `extension` (string) - File extension
- `size` (int) - File size in bytes
- `contentType` (string) - MIME content type

**Usage:**
```php
use CodebarAg\MFiles\DTO\DownloadedFile;

$downloadedFile = new DownloadedFile(
    content: $fileContent,
    name: 'document.pdf',
    extension: 'pdf',
    size: 1024,
    contentType: 'application/pdf'
);
```

### Property DTOs

#### Property

Represents a property in M-Files.

**Properties:**
- `propertyDef` (int) - Property definition ID
- `dataType` (MFDataTypeEnum) - Property data type
- `value` (mixed) - Property value

**Usage:**
```php
use CodebarAg\MFiles\DTO\Property;
use CodebarAg\MFiles\Enums\MFDataTypeEnum;

$property = new Property(
    propertyDef: 0,
    dataType: MFDataTypeEnum::TEXT,
    value: 'Sample Text'
);
```

#### PropertyValue

Represents a property value for creating documents.

**Properties:**
- `propertyDef` (int) - Property definition ID
- `dataType` (MFDataTypeEnum) - Property data type
- `value` (mixed) - Property value

**Methods:**
- `fromArray(int $propertyDef, MFDataTypeEnum $dataType, mixed $value): self` - Static factory method
- `toArray(): array` - Converts to array format for API requests

**Usage:**
```php
use CodebarAg\MFiles\DTO\PropertyValue;
use CodebarAg\MFiles\Enums\MFDataTypeEnum;

$propertyValue = new PropertyValue(
    propertyDef: 0,
    dataType: MFDataTypeEnum::TEXT,
    value: 'Sample Text'
);

// Using static factory method
$propertyValue = PropertyValue::fromArray(
    propertyDef: 0,
    dataType: MFDataTypeEnum::TEXT,
    value: 'Sample Text'
);

// Convert to array for API requests
$array = $propertyValue->toArray();
```

#### ObjectProperties

Represents object properties in M-Files.

**Properties:**
- `properties` (array) - Array of Property objects

**Usage:**
```php
use CodebarAg\MFiles\DTO\ObjectProperties;

$objectProperties = new ObjectProperties(properties: []);
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
