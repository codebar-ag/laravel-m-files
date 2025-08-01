<img src="https://banners.beyondco.de/Laravel%20M-Files.png?theme=light&packageManager=composer+require&packageName=codebar-ag%2Flaravel-m-files&pattern=circuitBoard&style=style_1&description=An+opinionated+way+to+integrate+M-Files+with+Laravel&md=1&showWatermark=0&fontSize=175px&images=document-report" alt="Laravel M-Files banner with description and logo">

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codebar-ag/laravel-m-files.svg?style=flat-square)](https://packagist.org/packages/codebar-ag/laravel-m-files)
[![GitHub-Tests](https://github.com/codebar-ag/laravel-m-files/actions/workflows/run-tests.yml/badge.svg?branch=main)](https://github.com/codebar-ag/laravel-m-files/actions/workflows/run-tests.yml)
[![GitHub Code Style](https://github.com/codebar-ag/laravel-m-files/actions/workflows/fix-php-code-style-issues.yml/badge.svg?branch=main)](https://github.com/codebar-ag/laravel-m-files/actions/workflows/fix-php-code-style-issues.yml)
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

## Default configuration
```env
M_FILES_URL=https://your-mfiles-server.com
M_FILES_USERNAME=your-username
M_FILES_PASSWORD=your-password
M_FILES_VAULT_GUID=ABC0DE2G-3HW-QWCQ-SDF3-WERWETWETW
M_FILES_CACHE_DRIVER=file
```

### Available Requests

#### Authentication
- `GetAuthenticationToken` - Get authentication token using username/password
- `LogOutFromVaultRequest` - Logout from vault with session ID

#### Vault Operations
- `GetVaultsRequest` - Get list of available vaults for the authenticated user

#### Object Operations
- `GetObjectPropertiesRequest` - Get object properties (works for documents, folders, and other object types)

#### File Operations
- `UploadFileRequest` - Upload a file to M-Files
- `CreateSingleFileDocumentRequest` - Create a single file document
- `DownloadFileRequest` - Download a file from M-Files

### Available Enums
- `MFDataTypeEnum` - Represents a data type in M-Files

### Available DTOs
- `Property` - Represents a property in M-Files
- `PropertyValue` - Represents a property value for creating documents
- `Document` - Represents a document in M-Files
- `Documents` - Represents a collection of documents
- `ObjectProperties` - Represents object properties in M-Files
- `File` - Represents a file in M-Files
- `DownloadedFile` - Represents a downloaded file with content and metadata
- `User` - Represents a user in M-Files

## Usage

### Basic Setup

```php
use CodebarAg\MFiles\Connectors\MFilesConnector;
use CodebarAg\MFiles\DTO\Config\ConfigWithCredentials;

$config = new ConfigWithCredentials();

// or for multi tenant applications

$config = new ConfigWithCredentials(
    url: 'https://your-mfiles-server.com',
    username: 'your-username',
    password: 'your-password',
    cacheDriver: 'file',
    vaultGuid: '{ABC0DE2G-3HW-QWCQ-SDF3-WERWETWETW}',
    authenticationToken: null // Optional to handle authentication manually, leave null to use automatic token management
);

$connector = new MFilesConnector(config: $config);
```

> By default authentication is handled automatically, to disable this, pass `authenticationToken: null` to the ConfigWithCredentials dto.

### Available Requests

#### Authentication

**Get Authentication Token**
```php
use CodebarAg\MFiles\Requests\Authentication\GetAuthenticationToken;
use CodebarAg\MFiles\DTO\Authentication\AuthenticationToken;

$request = new GetAuthenticationToken(
    url: 'https://your-mfiles-server.com',
    username: 'your-username',
    password: 'your-password',
    vaultGuid: '{ABC0DE2G-3HW-QWCQ-SDF3-WERWETWETW}',
);

$token = $request->send()->dto();
// Returns AuthenticationToken with sessionId
```

**Logout from Vault**
```php
use CodebarAg\MFiles\Requests\Authentication\LogOutFromVaultRequest;

$logout = (new LogOutFromVaultRequest(config: $config))->send()->dto();
// Returns true on successful logout, clears cached token
```

#### Vault Operations

**Get Available Vaults**
```php
use CodebarAg\MFiles\Requests\GetVaultsRequest;

$vaults = $connector->send(new GetVaultsRequest())->json();
// Returns array of available vaults for the authenticated user
```

#### Object Operations

**Get Object Properties**
```php
use CodebarAg\MFiles\Requests\GetObjectPropertiesRequest;
use CodebarAg\MFiles\DTO\ObjectProperties;

$properties = $connector->send(new GetObjectPropertiesRequest(
    objectType: 0, // 0 for documents, 1 for folders, etc.
    objectId: 123
))->dto();
// Returns ObjectProperties with property details

// With optional parameters
$properties = $connector->send(new GetObjectPropertiesRequest(
    objectType: 0,
    objectId: 123,
    includeDeleted: false
))->dto();
```

**Get Object Properties**
```php
use CodebarAg\MFiles\Requests\GetObjectPropertiesRequest;
use CodebarAg\MFiles\DTO\ObjectProperties;

$properties = $connector->send(new GetObjectPropertiesRequest(
    objectType: 0, // 0 for documents, 1 for folders, etc.
    objectId: 123
))->dto();
// Returns ObjectProperties with property details

// With optional parameters
$properties = $connector->send(new GetObjectPropertiesRequest(
    objectType: 0,
    objectId: 123,
    includeDeleted: false
))->dto();
```

#### File Operations

**Upload File**
```php
use CodebarAg\MFiles\Requests\UploadFileRequest;

$fileContent = file_get_contents('path/to/file.pdf');
$fileName = 'document.pdf';

$uploadedFile = $connector->send(new UploadFileRequest(
    fileContent: $fileContent,
    fileName: $fileName
))->dto();
// Returns array with file ID, title, extension
```

**Create Single File Document**
```php
use CodebarAg\MFiles\Requests\CreateSingleFileDocumentRequest;
use CodebarAg\MFiles\DTO\PropertyValue;
use CodebarAg\MFiles\Enums\MFDataTypeEnum;
use CodebarAg\MFiles\DTO\Document;

// First upload the file
$uploadedFile = $connector->send(new UploadFileRequest(
    fileContent: $fileContent,
    fileName: 'document.pdf'
))->dto();

// Then create the document
$document = $connector->send(new CreateSingleFileDocumentRequest(
    title: 'My Document',
    file: $uploadedFile
))->dto();
// Returns Document DTO with id, title, files, etc.

// With custom property values
$propertyValues = [
    new PropertyValue(propertyDef: 0, dataType: MFDataTypeEnum::TEXT, value: 'Custom Title'),
    new PropertyValue(propertyDef: 5, dataType: MFDataTypeEnum::DATE, value: '2024-01-01'),
];

$document = $connector->send(new CreateSingleFileDocumentRequest(
    title: 'Custom Document',
    file: $uploadedFile,
    propertyValues: $propertyValues
))->dto();
```

**Download File**
```php
use CodebarAg\MFiles\Requests\DownloadFileRequest;
use CodebarAg\MFiles\DTO\DownloadedFile;

$downloadedFile = $connector->send(new DownloadFileRequest(
    objectId: 123,
    fileId: 456
))->dto();
// Returns DownloadedFile with content, name, extension, size, contentType

// With optional parameters
$downloadedFile = $connector->send(new DownloadFileRequest(
    objectId: 123,
    fileId: 456,
    objectTypeId: 0,
    includeDeleted: false
))->dto();
```

### Complete Workflow Example

```php
use CodebarAg\MFiles\Connectors\MFilesConnector;
use CodebarAg\MFiles\DTO\Config\ConfigWithCredentials;
use CodebarAg\MFiles\Requests\GetVaultsRequest;
use CodebarAg\MFiles\Requests\GetObjectPropertiesRequest;
use CodebarAg\MFiles\Requests\CreateSingleFileDocumentRequest;
use CodebarAg\MFiles\Requests\UploadFileRequest;
use CodebarAg\MFiles\Requests\DownloadFileRequest;
use CodebarAg\MFiles\DTO\PropertyValue;
use CodebarAg\MFiles\Enums\MFDataTypeEnum;

$config = new ConfigWithCredentials(
    url: 'https://your-mfiles-server.com',
    username: 'your-username',
    password: 'your-password',
    identifier: 'default',
    cacheDriver: 'file',
    requestTimeoutInSeconds: 15,
    vaultGuid: 'ABC0DE2G-3HW-QWCQ-SDF3-WERWETWETW'
);

$connector = new MFilesConnector(config: $config);

// Get available vaults
$vaults = $connector->send(new GetVaultsRequest())->json();

// Get document properties (example with document ID 123)
$properties = $connector->send(new GetObjectPropertiesRequest(objectType: 0, objectId: 123))->dto();

// Upload a file
$fileContent = file_get_contents('document.pdf');
$uploadedFile = $connector->send(new UploadFileRequest(
    fileContent: $fileContent,
    fileName: 'document.pdf'
))->dto();

// Create a document with the uploaded file
$document = $connector->send(new CreateSingleFileDocumentRequest(
    title: 'My Document',
    file: $uploadedFile,
    propertyValues: [
        new PropertyValue(propertyDef: 0, dataType: MFDataTypeEnum::TEXT, value: 'Custom Title'),
        new PropertyValue(propertyDef: 5, dataType: MFDataTypeEnum::DATE, value: '2024-01-01'),
    ]
))->dto();

// Download a file from a document
$downloadedFile = $connector->send(new DownloadFileRequest(
    objectId: $document->id,
    fileId: 101
))->dto();


```

## Authentication Token Management

The package supports M-Files authentication tokens with automatic token management:

- **Automatic Token Generation**: Tokens are automatically generated using username/password
- **Token Caching**: Tokens are cached to reduce API calls
- **Flexible Configuration**: Support for multiple configurations through `ConfigWithCredentials`

## Configuration Management

For applications that need to connect to multiple M-Files instances, you can manage different configurations by creating separate `ConfigWithCredentials` instances.
You can store these configurations in your application's configuration files, environment variables, or database.

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

- [Rhys Lees](https://github.com/rhyslees)
- [Codebar Solutions AG](https://github.com/codebar-ag)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
