<img src="https://banners.beyondco.de/Laravel%20DocuWare.png?theme=light&packageManager=composer+require&packageName=codebar-ag%2Flaravel-m-files&pattern=circuitBoard&style=style_1&description=An+opinionated+way+to+integrate+M-Files+with+Laravel&md=1&showWatermark=0&fontSize=175px&images=document-report">

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
M_FILES_IDENTIFIER=default
M_FILES_VAULT_GUID=ABC0DE2G-3HW-QWCQ-SDF3-WERWETWETW
M_FILES_CACHE_DRIVER=file
```

## Usage

### Basic Usage with Saloon

```php
use CodebarAg\MFiles\Connectors\MFilesConnector;
use CodebarAg\MFiles\DTO\Config\ConfigWithCredentials;
use CodebarAg\MFiles\DTO\Authentication\AuthenticationToken;
use CodebarAg\MFiles\Requests\GetCurrentUserRequest;
use CodebarAg\MFiles\Requests\GetDocumentsRequest;
use CodebarAg\MFiles\Requests\CreateSingleFileDocumentRequest;
use CodebarAg\MFiles\Requests\UploadFileRequest;
use CodebarAg\MFiles\Requests\DownloadFileRequest;
use CodebarAg\MFiles\DTO\PropertyValue;
use CodebarAg\MFiles\Enums\MFDataTypeEnum;
use Illuminate\Support\Arr;

$config = new ConfigWithCredentials(
    url: 'https://your-mfiles-server.com',
    username: 'your-username',
    password: 'your-password',
    identifier: 'default',
    cacheDriver: 'file',
    requestTimeoutInSeconds: 15,
    vaultGuid: 'ABC0DE2G-3HW-QWCQ-SDF3-WERWETWETW',
    authenticationToken: $authenticationToken // ptional to handle authenication manually, leave null to use automatic token management based on url/username/password
);

// Create connector
$connector = new MFilesConnector($config);

// Make requests
$user = $connector->send(new GetCurrentUserRequest())->dto();
$documents = $connector->send(new GetDocumentsRequest())->dto();

// Upload a file first
$uploadedFile = $connector->send(new UploadFileRequest(
    fileContent: $fileContent,
    fileName: 'document.pdf'
))->dto();

// Create a single file document
$document = $connector->send(new CreateSingleFileDocumentRequest(
    title: 'My Document',
    file: $uploadedFile,
    propertyValues: [ // optional
        new PropertyValue(0, MFDataTypeEnum::TEXT, 'Custom Title'),
        new PropertyValue(5, MFDataTypeEnum::DATE, '2024-01-01'),
    ]
))->dto();

$documents = $connector->send(new GetDocumentsRequest())->dto();
$document = $documents->first();
$file = $document->files->first();

$downloadedFile = $connector->send(new DownloadFileRequest(
    objectId: $document->id,
    fileId: $file->id
))->dto();


```

### Available Requests

#### Authentication
- `GetAuthenticationToken` - Get authentication token using username/password
- `LogoutSession` - Logout a session with session ID

#### User Operations
- `GetCurrentUserRequest` - Get information about the current authenticated user

#### Document Operations
- `GetDocumentsRequest` - Get documents with optional filtering
- `GetDocumentPropertiesRequest` - Get document properties

#### File Operations
- `UploadFileRequest` - Upload a file to M-Files
- `CreateSingleFileDocumentRequest` - Create a single file document
- `DownloadFileRequest` - Download a file from M-Files

### Available DTOs
- `Property` - Represents a property in M-Files
- `PropertyValue` - Represents a property value for creating documents
- `Document` - Represents a document in M-Files
- `Documents` - Represents a collection of documents
- `File` - Represents a file in M-Files
- `DownloadedFile` - Represents a downloaded file with content and metadata
- `User` - Represents a user in M-Files


### Available Enums
- `MFDataTypeEnum` - Represents a data type in M-Files

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
