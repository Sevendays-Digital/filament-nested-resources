<?php

namespace SevendaysDigital\FilamentNestedResources;

use Filament\PluginServiceProvider;
use Spatie\LaravelPackageTools\Package;

class FilamentNestedResourcesServiceProvider extends PluginServiceProvider
{
    public static string $name = 'filament-nested-resources';

    protected array $resources = [
        // CustomResource::class,
    ];

    protected array $pages = [
        // CustomPage::class,
    ];

    protected array $widgets = [
        // CustomWidget::class,
    ];

    protected array $styles = [
        'plugin-filament-nested-resources' => __DIR__.'/../resources/dist/filament-nested-resources.css',
    ];

    protected array $scripts = [
        'plugin-filament-nested-resources' => __DIR__.'/../resources/dist/filament-nested-resources.js',
    ];

    // protected array $beforeCoreScripts = [
    //     'plugin-filament-nested-resources' => __DIR__ . '/../resources/dist/filament-nested-resources.js',
    // ];

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name);
    }
}
