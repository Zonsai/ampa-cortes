<?php

namespace App\Filament\Resources\ConsentTypes;

use App\Enums\ConsentResponseStatus;
use App\Filament\Resources\ConsentTypes\Pages\CreateConsentType;
use App\Filament\Resources\ConsentTypes\Pages\EditConsentType;
use App\Filament\Resources\ConsentTypes\Pages\ListConsentTypes;
use App\Filament\Resources\ConsentTypes\RelationManagers\ConsentResponsesRelationManager;
use App\Filament\Resources\ConsentTypes\RelationManagers\ConsentVersionsRelationManager;
use App\Filament\Resources\ConsentTypes\Schemas\ConsentTypeForm;
use App\Filament\Resources\ConsentTypes\Tables\ConsentTypesTable;
use App\Models\ConsentType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ConsentTypeResource extends Resource
{
    protected static ?string $model = ConsentType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'Consentimientos';

    protected static ?string $modelLabel = 'Tipo de consentimiento';

    protected static ?string $pluralModelLabel = 'Consentimientos';

    public static function getNavigationGroup(): ?string
    {
        return 'Consentimientos';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function form(Schema $schema): Schema
    {
        return ConsentTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ConsentTypesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ConsentVersionsRelationManager::class,
            ConsentResponsesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConsentTypes::route('/'),
            'create' => CreateConsentType::route('/create'),
            'edit' => EditConsentType::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('versions')
            ->withCount(['responses as pending_count' => fn (Builder $q) => $q->where('status', ConsentResponseStatus::Pending->value)])
            ->withCount(['responses as accepted_count' => fn (Builder $q) => $q->where('status', ConsentResponseStatus::Accepted->value)])
            ->withCount(['responses as rejected_count' => fn (Builder $q) => $q->where('status', ConsentResponseStatus::Rejected->value)])
            ->withCount(['responses as revoked_count' => fn (Builder $q) => $q->where('status', ConsentResponseStatus::Revoked->value)])
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
