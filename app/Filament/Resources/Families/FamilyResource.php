<?php

namespace App\Filament\Resources\Families;

use App\Filament\Resources\Families\Pages\CreateFamily;
use App\Filament\Resources\Families\Pages\EditFamily;
use App\Filament\Resources\Families\Pages\ListFamilies;
use App\Filament\Resources\Families\RelationManagers\ConsentResponsesRelationManager;
use App\Filament\Resources\Families\RelationManagers\EnrollmentsRelationManager;
use App\Filament\Resources\Families\RelationManagers\FormResponsesRelationManager;
use App\Filament\Resources\Families\RelationManagers\GuardiansRelationManager;
use App\Filament\Resources\Families\RelationManagers\StudentsRelationManager;
use App\Filament\Resources\Families\Schemas\FamilyForm;
use App\Filament\Resources\Families\Tables\FamiliesTable;
use App\Models\Family;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FamilyResource extends Resource
{
    protected static ?string $model = Family::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;

    protected static ?string $navigationLabel = 'Familias';

    protected static ?string $modelLabel = 'Familia';

    protected static ?string $pluralModelLabel = 'Familias';

    public static function getNavigationGroup(): ?string
    {
        return 'Familias';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function form(Schema $schema): Schema
    {
        return FamilyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FamiliesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            GuardiansRelationManager::class,
            StudentsRelationManager::class,
            EnrollmentsRelationManager::class,
            FormResponsesRelationManager::class,
            ConsentResponsesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFamilies::route('/'),
            'create' => CreateFamily::route('/create'),
            'edit' => EditFamily::route('/{record}/edit'),
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
