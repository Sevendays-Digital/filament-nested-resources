<?php

namespace SevendaysDigital\FilamentNestedResources\ResourcePages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use SevendaysDigital\FilamentNestedResources\NestedResource;

abstract class NestedCreateRecord extends CreateRecord
{
    use NestedPageTrait;

    public mixed $urlParameters;

    public function mount(): void
    {
        $this->urlParameters = Route::current()->parameters;
        parent::mount();
    }

    protected function handleRecordCreation(array $data): Model
    {
        /** @var NestedResource $resource */
        $resource = $this::getResource();

        $parent = Str::camel(Str::afterLast($resource::getParent()::getModel(), '\\'));

        // Create the model.
        $model = $this->getModel()::make($data);
        $model->{$parent}()->associate($this->urlParameters[$parent]);
        $model->save();

        return $model;
    }
}
