<?php

namespace App\Filament\Resources\ExtracurricularActivities;

use App\Filament\Resources\ExtracurricularActivities\Pages\CreateExtracurricularActivity;
use App\Filament\Resources\ExtracurricularActivities\Pages\EditExtracurricularActivity;
use App\Filament\Resources\ExtracurricularActivities\Pages\ListExtracurricularActivities;
use App\Filament\Resources\ExtracurricularActivities\RelationManagers\ActivityGroupsRelationManager;
use App\Filament\Resources\ExtracurricularActivities\RelationManagers\EnrollmentsRelationManager;
use App\Filament\Resources\ExtracurricularActivities\Schemas\ExtracurricularActivityForm;
use App\Filament\Resources\ExtracurricularActivities\Tables\ExtracurricularActivitiesTable;
use App\Models\ExtracurricularActivity;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExtracurricularActivityResource extends Resource
{
    protected static ?string $model = ExtracurricularActivity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $navigationLabel = 'Extraescolares';

    protected static ?string $modelLabel = 'Actividad extraescolar';

    protected static ?string $pluralModelLabel = 'Actividades extraescolares';

    public static function getNavigationGroup(): ?string
    {
        return 'Extraescolares';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function form(Schema $schema): Schema
    {
        return ExtracurricularActivityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExtracurricularActivitiesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ActivityGroupsRelationManager::class,
            EnrollmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExtracurricularActivities::route('/'),
            'create' => CreateExtracurricularActivity::route('/create'),
            'edit' => EditExtracurricularActivity::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
