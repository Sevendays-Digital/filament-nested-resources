<?php

namespace SevendaysDigital\FilamentNestedResources\ResourcePages;

use Filament\Pages\Actions\CreateAction;
use Filament\Pages\Actions\DeleteAction;
use Filament\Resources\Form;
use Filament\Tables\Actions\DeleteAction as FilamentDeleteAction;
use Filament\Tables\Actions\EditAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use SevendaysDigital\FilamentNestedResources\NestedResource;

/**
 * @mixin Filament\Resources\Pages\EditRecord
 */
trait NestedPage
{
    public array $urlParameters;

    /**
     * @return class-string<NestedResource>|NestedResource
     */
    abstract public static function getResource(): string;

    public function bootNestedPage()
    {
        if (empty($this->urlParameters)) {
            $this->urlParameters = $this->getUrlParametersForState();
        }
    }

    public function mountNestedPage()
    {
        if (empty($this->urlParameters)) {
            $this->urlParameters = $this->getUrlParametersForState();
        }
    }

    protected function getUrlParametersForState(): array
    {
        $parameters = Route::current()->parameters;

        foreach ($parameters as $key => $value) {
            if ($value instanceof Model) {
                $parameters[$key] = $value->getKey();
            }
        }

        return $parameters;
    }

    protected function getBreadcrumbs(): array
    {
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

    protected function getTableQuery(): Builder
    {
        $urlParams = array_values($this->urlParameters);
        $parameter = array_pop($urlParams);

        return static::getResource()::getEloquentQuery($parameter);
    }

    protected function configureEditAction(\Filament\Pages\Actions\EditAction|EditAction $action): void
    {
        $resource = static::getResource();

        if ($action instanceof EditAction) {
            $action
                ->authorize(fn (Model $record): bool => $resource::canEdit($record))
                ->form(fn (): array => $this->getEditFormSchema());

            if ($resource::hasPage('edit')) {
                $action->url(fn (Model $record): string => $resource::getUrl(
                    'edit',
                    [...$this->urlParameters, 'record' => $record]
                ));
            }
        } else {
            $action
                ->authorize($resource::canEdit($this->getRecord()))
                ->record($this->getRecord())
                ->recordTitle($this->getRecordTitle());

            if ($resource::hasPage('edit')) {
                $action->url(fn (): string => static::getResource()::getUrl('edit', ['record' => $this->getRecord()]));

                return;
            }

            $action->form($this->getFormSchema());
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

    protected function configureDeleteAction(DeleteAction|FilamentDeleteAction $action): void
    {
        $resource = static::getResource();

        $action
            ->authorize($resource::canDelete($this->getRecord()))
            ->record($this->getRecord())
            ->recordTitle($this->getRecordTitle())
            ->successRedirectUrl($resource::getUrl('index', $this->urlParameters));
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

    protected function form(Form $form): Form
    {
        return static::getResource()::form($form, $this->getParent());
    }
}
