<?php

namespace App\Filament\Resources\SchoolStages;

use App\Filament\Resources\SchoolStages\Pages\CreateSchoolStage;
use App\Filament\Resources\SchoolStages\Pages\EditSchoolStage;
use App\Filament\Resources\SchoolStages\Pages\ListSchoolStages;
use App\Filament\Resources\SchoolStages\Schemas\SchoolStageForm;
use App\Filament\Resources\SchoolStages\Tables\SchoolStagesTable;
use App\Models\SchoolStage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SchoolStageResource extends Resource
{
    protected static ?string $model = SchoolStage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?string $navigationLabel = 'Etapas educativas';

    protected static ?string $modelLabel = 'Etapa educativa';

    protected static ?string $pluralModelLabel = 'Etapas educativas';

    public static function getNavigationGroup(): ?string
    {
        return 'Configuración';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function form(Schema $schema): Schema
    {
        return SchoolStageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SchoolStagesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSchoolStages::route('/'),
            'create' => CreateSchoolStage::route('/create'),
            'edit' => EditSchoolStage::route('/{record}/edit'),
        ];
    }
}
