<?php

namespace SevendaysDigital\FilamentNestedResources\ResourcePages;

use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use SevendaysDigital\FilamentNestedResources\NestedResource;
use Illuminate\Support\Str;

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

    protected function getParentId(): string|int
    {
        /** @var NestedResource $resource */
        $resource = $this::getResource();

        $parent = Str::camel(Str::afterLast($resource::getParent()::getModel(), '\\'));

        if ($this->urlParameters[$parent] instanceof Model) {
            return $this->urlParameters[$parent]->getKey();
        }

        if (is_array($this->urlParameters[$parent]) && isset($this->urlParameters[$parent]['id'])) {
            return $this->urlParameters[$parent]['id'];
        }

        return $this->urlParameters[$parent];
    }

    public function getParent(): Model
    {
        $resource = $this::getResource();

        return $resource::getParent()::getModel()::find($this->getParentId());
    }
}
