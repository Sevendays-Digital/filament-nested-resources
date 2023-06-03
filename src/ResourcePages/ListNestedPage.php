<?php

namespace Mstfkhazaal\FilamentNestedresources\ResourcePages;

use Filament\Pages\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteAction as FilamentDeleteAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait ListNestedPage
{
    protected function configureDeleteAction(DeleteAction|FilamentDeleteAction $action): void
    {
        $resource = static::getResource();
        $action
            ->authorize(fn (Model $record): bool => $resource::canDelete($record));
    }

}
