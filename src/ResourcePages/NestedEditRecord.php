<?php

namespace SevendaysDigital\FilamentNestedResources\ResourcePages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Route;

abstract class NestedEditRecord extends EditRecord
{
    use NestedPageTrait;
    public mixed $urlParameters;

    public function mount($record): void
    {
        $this->urlParameters = Route::current()->parameters();
        parent::mount($record);
    }
}
