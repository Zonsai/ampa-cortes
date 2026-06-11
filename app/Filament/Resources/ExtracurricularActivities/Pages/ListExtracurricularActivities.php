<?php

namespace App\Filament\Resources\ExtracurricularActivities\Pages;

use App\Filament\Resources\ExtracurricularActivities\ExtracurricularActivityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExtracurricularActivities extends ListRecords
{
    protected static string $resource = ExtracurricularActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
