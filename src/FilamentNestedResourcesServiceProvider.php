<?php

namespace SevendaysDigital\FilamentNestedResources;

use Filament\PluginServiceProvider;
use Spatie\LaravelPackageTools\Package;

class FilamentNestedResourcesServiceProvider extends PluginServiceProvider
{
    public static string $name = 'filament-nested-resources';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name);
    }
}
