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

        foreach ($this->urlParameters as $key => $value) {
            if ($value instanceof Model) {
                $this->urlParameters[$key] = $value->getKey();
            }
        }

        parent::mount();
    }

    protected function handleRecordCreation(array $data): Model
    {
        /** @var NestedResource $resource */
        $resource = $this::getResource();

        $parent = Str::camel(Str::afterLast($resource::getParent()::getModel(), '\\'));

        // Create the model.
        $model = $this->getModel()::make($data);
        $model->{$parent}()->associate($this->getParentId());
        $model->save();

        return $model;
    }
}
