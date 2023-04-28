<?php

namespace SevendaysDigital\FilamentNestedResources\ResourcePages;

use Closure;
use Filament\Pages\Actions\CreateAction;
use Filament\Pages\Actions\DeleteAction;
use Filament\Pages\Actions\EditAction as PageEditAction;
use Filament\Pages\Actions\ViewAction;
use Filament\Resources\Form;
use Filament\Resources\Pages\ViewRecord;
use Filament\Tables\Actions\DeleteAction as TableDeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction as TableViewAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use SevendaysDigital\FilamentNestedResources\NestedResource;

/**
 * @extends \Filament\Resources\Pages\EditRecord
 * @extends \Filament\Resources\Pages\ViewRecord
 * @extends \Filament\Resources\Pages\ListRecords
 */
trait NestedPage
{
    public array $urlParameters;

    /**
     * @return class-string<\SevendaysDigital\FilamentNestedResources\NestedResource>
     */
    abstract public static function getResource(): string;

    public function bootNestedPage(): void
    {
        if (empty($this->urlParameters)) {
            $this->urlParameters = $this->getUrlParametersForState();
        }
    }

    public function mountNestedPage(): void
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
        foreach ($resource::getParentTree(static::getResource()::getParent(), $this->urlParameters) as $i => $nested) {
            // Here we check if we can view and/or edit a record, if not we replace the link with a #.
            // List.
            if ($nested->resource::canViewAny()) {
                $nestedCrumbs[$nested->getListUrl()] = $nested->resource::getBreadcrumb();
            } else {
                $nestedCrumbs[] = $nested->resource::getBreadcrumb();
            }

            // Edit.
            if (($record = $nested->getRecord()) && $nested->resource::canEdit($record)) {
                $nestedCrumbs[$nested->getEditUrl()] = $nested->getBreadcrumbTitle();
            } else {
                $nestedCrumbs[] = $nested->getBreadcrumbTitle();
            }
        }

        // Add the current list entry.
        if ($resource::canViewAny()) {
            $currentListUrl = $resource::getUrl(
                'index',
                $resource::getParentParametersForUrl($resource::getParent(), $this->urlParameters)
            );
            $nestedCrumbs[$currentListUrl] = $resource::getBreadcrumb();
        } else {
            $nestedCrumbs[] = $resource::getBreadcrumb();
        }

        // If it is a view page we need to add the current entry.
        if ($this instanceof ViewRecord) {
            if ($resource::canEdit($this->record)) {
                $nestedCrumbs[$resource::getUrl('edit', $this->urlParameters)] = $this->getTitle();
            } else {
                $nestedCrumbs[] = $this->getTitle();
            }
        }

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

    protected function configureEditAction(PageEditAction|EditAction $action): void
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
                $action->url(fn (): string => static::getResource()::getUrl(
                    'edit',
                    [...$this->urlParameters, 'record' => $this->getRecord()]
                ));

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

    protected function configureDeleteAction(DeleteAction|TableDeleteAction $action): void
    {
        $resource = static::getResource();

        $action
            ->authorize($resource::canDelete($this->getRecord()))
            ->record($this->getRecord())
            ->recordTitle($this->getRecordTitle())
            ->successRedirectUrl($resource::getUrl('index', $this->urlParameters));
    }

    protected function configureViewAction(ViewAction|TableViewAction $action): void
    {
        $resource = static::getResource();

        if ($action instanceof TableViewAction) {
            $action
                ->authorize(fn (Model $record): bool => $resource::canView($record))
                ->form(fn (): array => $this->getViewFormSchema());

            if ($resource::hasPage('view')) {
                $action->url(fn (Model $record): string => $resource::getUrl('view', ['record' => $record]));
            }
        } else {
            $action
                ->authorize($resource::canView($this->getRecord()))
                ->record($this->getRecord())
                ->recordTitle($this->getRecordTitle());

            if ($resource::hasPage('view')) {
                $action->url(fn (): string => static::getResource()::getUrl('view', [...$this->urlParameters, 'record' => $this->getRecord()]));

                return;
            }

            $action->form($this->getFormSchema());
        }
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

    protected function getTableRecordUrlUsing(): ?Closure
    {
        return function (Model $record): ?string {
            foreach (['view', 'edit'] as $action) {
                $action = $this->getCachedTableAction($action);

                if (! $action) {
                    continue;
                }

                $action->record($record);

                if ($action->isHidden()) {
                    continue;
                }

                $url = $action->getUrl();

                if (! $url) {
                    continue;
                }

                return $url;
            }

            $resource = static::getResource();

            foreach (['view', 'edit'] as $action) {
                if (! $resource::hasPage($action)) {
                    continue;
                }

                if (! $resource::{'can'.ucfirst($action)}($record)) {
                    continue;
                }

                return $resource::getUrl($action, [...$this->urlParameters, 'record' => $record]);
            }

            return null;
        };
    }
}
