<?php

namespace SevendaysDigital\FilamentNestedResources\ResourcePages;

use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Route;

class NestedViewRecord extends ViewRecord
{
    use NestedPageTrait;

    public mixed $urlParameters;

    public function mount($record): void
    {
        $this->urlParameters = Route::current()->parameters();
        parent::mount($record);
    }
}
