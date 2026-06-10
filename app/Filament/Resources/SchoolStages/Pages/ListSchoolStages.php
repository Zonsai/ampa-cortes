<?php

namespace App\Filament\Resources\SchoolStages\Pages;

use App\Filament\Resources\SchoolStages\SchoolStageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSchoolStages extends ListRecords
{
    protected static string $resource = SchoolStageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
