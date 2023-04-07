<?php

namespace SevendaysDigital\FilamentNestedResources\ResourcePages;

use Filament\Pages\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\EditAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

abstract class NestedListRecords extends ListRecords
{
    use NestedPageTrait;

    public mixed $urlParameters;

    public function mount(): void
    {
        $this->urlParameters = Route::current()->parameters();
        parent::mount();
    }

    protected function getTableQuery(): Builder
    {
        $urlParams = array_values($this->urlParameters);
        $parameter = array_pop($urlParams)[0];

        return static::getResource()::getEloquentQuery($parameter);
    }

    protected function configureEditAction(EditAction $action): void
    {
        $resource = static::getResource();

        $action
            ->authorize(fn (Model $record): bool => $resource::canEdit($record))
            ->form(fn (): array => $this->getEditFormSchema());

        if ($resource::hasPage('edit')) {
            $action->url(fn (Model $record): string => $resource::getUrl('edit',
                [...$this->urlParameters, 'record' => $record]));
        }
    }

    protected function configureCreateAction(CreateAction $action): void
    {
        $resource = static::getResource();

        $action
            ->authorize($resource::canCreate())
            ->model($this->getModel())
            ->modelLabel($this->getModelLabel())
            ->form(fn (): array => $this->getCreateFormSchema());

        if ($resource::hasPage('create')) {
            $action->url(fn (): string => $resource::getUrl('create', $this->urlParameters));
        }
    }
}
