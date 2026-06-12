<?php

namespace App\Filament\Resources\ConsentTypes\Pages;

use App\Filament\Resources\ConsentTypes\ConsentTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditConsentType extends EditRecord
{
    protected static string $resource = ConsentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
