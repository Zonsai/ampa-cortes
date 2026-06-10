<?php

namespace App\Filament\Resources\SchoolStages\Pages;

use App\Filament\Resources\SchoolStages\SchoolStageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSchoolStage extends EditRecord
{
    protected static string $resource = SchoolStageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
