<?php

namespace SevendaysDigital\FilamentNestedResources\ResourcePages;

use Filament\Resources\Resource;
use SevendaysDigital\FilamentNestedResources\NestedResource;

trait NestedPageTrait
{
    protected function getBreadcrumbs(): array
    {
        /** @var resource|NestedResource $resource */
        $resource = static::getResource();

        // Build the nested breadcrumbs.
        $nestedCrumbs = [];
        foreach ($resource::getParentTree(static::getResource()::getParent(), $this->urlParameters) as $nested) {
            $nestedCrumbs[$nested->getListUrl()] = $nested->resource::getBreadcrumb();
            $nestedCrumbs[$nested->getEditUrl()] = $nested->getBreadcrumbTitle();
        }

        // Add the current list entry.
        $currentListUrl = $resource::getUrl(
            'index',
            $resource::getParentParametersForUrl($resource::getParent(), $this->urlParameters)
        );
        $nestedCrumbs[$currentListUrl] = $resource::getBreadcrumb();

        // Finalize with the current url.
        $breadcrumb = $this->getBreadcrumb();
        if (filled($breadcrumb)) {
            $nestedCrumbs[] = $breadcrumb;
        }

        return $nestedCrumbs;
    }

    protected function getRedirectUrl(): string
    {
        $resource = static::getResource();

        if ($resource::hasPage('view') && $resource::canView($this->record)) {
            return $resource::getUrl('view', [...$this->urlParameters, 'record' => $this->record]);
        }

        if ($resource::hasPage('edit') && $resource::canEdit($this->record)) {
            return $resource::getUrl('edit', [...$this->urlParameters, 'record' => $this->record]);
        }

        return $resource::getUrl('index', $this->urlParameters);
    }
}
