<?php

namespace SevendaysDigital\FilamentNestedResources\ResourcePages;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait HasTranslatableNestedPage
{
    /**
     * Handle Record Creation
     * It's a combination of the "handleRecordCreation()" function that exists in the "Filament\Resources\Pages\CreateRecord\Concerns\Translatable" and "SevendaysDigital\FilamentNestedResources\ResourcePages\NestedPage"
     *
     * @param array $data
     * @return Model
     */
    protected function handleRecordCreation(array $data): Model
    {
        $record = app(static::getModel());

        // "Translatable" Logic
        $record = app(static::getModel());
        $record->fill(Arr::except($data, $record->getTranslatableAttributes()));
        foreach (Arr::only($data, $record->getTranslatableAttributes()) as $key => $value) {
            $record->setTranslation($key, $this->activeFormLocale, $value);
        }

        // "NestedPage" Logic
        $resource = $this::getResource();
        $parent = Str::camel(Str::afterLast($resource::getParent()::getModel(), '\\'));
        $record->{$parent}()->associate($this->getParentId());

        $record->save();

        return $record;
    }
}
