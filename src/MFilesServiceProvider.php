<?php

declare(strict_types=1);

namespace CodebarAg\MFiles;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MFilesServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-m-files')
            ->hasConfigFile('m-files');
    }
}
