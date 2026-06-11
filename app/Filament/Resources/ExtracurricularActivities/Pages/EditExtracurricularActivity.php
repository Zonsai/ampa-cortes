<?php

namespace App\Filament\Resources\ExtracurricularActivities\Pages;

use App\Filament\Resources\ExtracurricularActivities\ExtracurricularActivityResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditExtracurricularActivity extends EditRecord
{
    protected static string $resource = ExtracurricularActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
