<?php

namespace SevendaysDigital\FilamentNestedResources;

use Filament\Resources\Resource;

class NestedEntry
{
    public function __construct(
        public string $urlPlaceholder,
        public string $urlPart,
        /** @var NestedResource|resource $resouce */
        public string $resource,
        public string $label,
        public mixed $id,
        public array $urlParams,
    ) {
    }

    public function getListUrl(): string
    {
        $params = $this->urlParams;
        array_pop($params);

        return $this->resource::getUrl('index', $params);
    }

    public function getEditUrl(): string
    {
        $params = $this->urlParams;
        array_pop($params);

        return $this->resource::getUrl('edit', [...$params, 'record' => $this->id]);
    }

    public function getBreadcrumbTitle(): string
    {
        return $this->resource::getRecordTitle($this->resource::getModel()::find($this->id));
    }
}
