<?php

namespace SevendaysDigital\FilamentNestedResources;

use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

class NestedEntry
{
    public function __construct(
        public string $urlPlaceholder,
        public string $urlPart,
        /** @var class-string<resource> $resource */
        public string $resource,
        public string $label,
        public null|string|int $id,
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

        return $this->resource::getUrl('edit', [...$params, 'record' => $this->id()]);
    }

    public function getRecord(): Model
    {
        return $this->resource::resolveRecordRouteBinding($this->id);
    }

    public function getBreadcrumbTitle(): string
    {
        return $this->resource::getRecordTitle($this->resource::getModel()::find($this->id()));
    }

    private function id(): string|int|null
    {
        return $this->id;
    }
}
