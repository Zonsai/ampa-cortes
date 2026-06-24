<?php

namespace App\Filament\Resources\ActivityGroups;

use App\Filament\Resources\ActivityGroups\Pages\EditActivityGroup;
use App\Filament\Resources\ActivityGroups\RelationManagers\ExceptionsRelationManager;
use App\Filament\Resources\ActivityGroups\Schemas\ActivityGroupForm;
use App\Models\ActivityGroup;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;

class ActivityGroupResource extends Resource
{
    protected static ?string $model = ActivityGroup::class;

    protected static ?string $modelLabel = 'Grupo';

    protected static ?string $pluralModelLabel = 'Grupos';

    public static function form(Schema $schema): Schema
    {
        return ActivityGroupForm::configure($schema);
    }

    public static function getRelations(): array
    {
        return [
            ExceptionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'edit' => EditActivityGroup::route('/{record}/edit'),
        ];
    }
}
