<?php

namespace App\Filament\Resources\ConsentTypes\RelationManagers;

use App\Enums\ConsentResponseStatus;
use App\Models\ConsentHistory;
use App\Models\ConsentResponse;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ConsentResponsesRelationManager extends RelationManager
{
    protected static string $relationship = 'responses';

    protected static ?string $title = 'Respuestas de familias';

    protected static ?string $modelLabel = 'respuesta';

    protected static ?string $pluralModelLabel = 'respuestas';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('consentType'))
            ->columns([
                TextColumn::make('family.name')
                    ->label('Familia')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('student_display')
                    ->label('Alumno/a')
                    ->getStateUsing(fn (ConsentResponse $record): string => $record->student
                        ? $record->student->last_name.', '.$record->student->first_name
                        : '—'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('consentVersion.version_number')
                    ->label('Versión')
                    ->alignCenter(),
                TextColumn::make('responded_at')
                    ->label('Respondido')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('revoked_at')
                    ->label('Revocado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('respondedBy.name')
                    ->label('Respondido por')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('image_review_flag')
                    ->label('Rev. imagen')
                    ->getStateUsing(fn (ConsentResponse $record): ?string => $record->status === ConsentResponseStatus::Revoked
                        && $record->consentType->requires_image_review
                            ? 'Revisar'
                            : null
                    )
                    ->badge()
                    ->color('warning')
                    ->placeholder('')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(ConsentResponseStatus::class),
            ])
            ->recordActions([
                Action::make('ver_historial')
                    ->label('Historial')
                    ->icon(Heroicon::ClipboardDocumentList)
                    ->color('gray')
                    ->fillForm(fn (ConsentResponse $record): array => [
                        'historial' => $record->load('histories.performedBy')
                            ->histories
                            ->map(fn (ConsentHistory $h): string => sprintf(
                                '[%s] %s — %s%s',
                                $h->created_at->format('d/m/Y H:i'),
                                $h->event_type->getLabel(),
                                $h->performedBy?->name ?? 'Sistema',
                                $h->notes ? "\n  Nota: {$h->notes}" : '',
                            ))
                            ->join("\n\n"),
                    ])
                    ->schema([
                        Textarea::make('historial')
                            ->label('Eventos')
                            ->rows(10)
                            ->disabled(),
                    ])
                    ->modalHeading(fn (ConsentResponse $record): string => "Historial — {$record->family->name}")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
            ]);
    }
}
