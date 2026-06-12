<?php

namespace App\Filament\Resources\ConsentTypes\Pages;

use App\Filament\Resources\ConsentTypes\ConsentTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListConsentTypes extends ListRecords
{
    protected static string $resource = ConsentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
