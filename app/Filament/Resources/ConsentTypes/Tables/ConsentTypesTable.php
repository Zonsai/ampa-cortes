<?php

namespace App\Filament\Resources\ConsentTypes\Tables;

use App\Actions\Consents\PublishConsentTypeAction;
use App\Actions\Consents\PublishNewConsentVersionAction;
use App\Enums\ConsentScope;
use App\Enums\ConsentTypeStatus;
use App\Exports\Consents\ConsentHistoryExport;
use App\Exports\Consents\ConsentStatusExport;
use App\Models\ConsentType;
use App\Models\ConsentVersion;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class ConsentTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('scope')
                    ->label('Alcance')
                    ->badge(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                IconColumn::make('is_rejectable')
                    ->label('Rechazable')
                    ->boolean(),
                IconColumn::make('is_revocable')
                    ->label('Revocable')
                    ->boolean(),
                IconColumn::make('requires_image_review')
                    ->label('Rev. imagen')
                    ->boolean()
                    ->trueColor('warning')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('versions_count')
                    ->label('Versiones')
                    ->alignCenter(),
                TextColumn::make('pending_count')
                    ->label('Pendientes')
                    ->alignCenter(),
                TextColumn::make('accepted_count')
                    ->label('Aceptados')
                    ->alignCenter(),
                TextColumn::make('rejected_count')
                    ->label('Rechazados')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('revoked_count')
                    ->label('Revocados')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(ConsentTypeStatus::class),
                SelectFilter::make('scope')
                    ->label('Alcance')
                    ->options(ConsentScope::class),
                SelectFilter::make('is_rejectable')
                    ->label('Rechazable')
                    ->options(['1' => 'Sí', '0' => 'No']),
                SelectFilter::make('is_revocable')
                    ->label('Revocable')
                    ->options(['1' => 'Sí', '0' => 'No']),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('publicar')
                    ->label('Publicar')
                    ->icon(Heroicon::CheckCircle)
                    ->color('success')
                    ->visible(fn (ConsentType $record): bool => $record->status === ConsentTypeStatus::Draft)
                    ->modalHeading('Publicar consentimiento')
                    ->schema([
                        Textarea::make('legal_text')
                            ->label('Texto legal')
                            ->rows(6)
                            ->required(),
                        TextInput::make('summary')
                            ->label('Resumen breve')
                            ->maxLength(255),
                        DatePicker::make('effective_from')
                            ->label('Vigente desde'),
                    ])
                    ->action(function (ConsentType $record, array $data): void {
                        try {
                            DB::transaction(function () use ($record, $data): void {
                                $version = ConsentVersion::create([
                                    'consent_type_id' => $record->id,
                                    'version_number' => 1,
                                    'legal_text' => $data['legal_text'],
                                    'summary' => $data['summary'] ?? null,
                                    'effective_from' => $data['effective_from'] ?? null,
                                    'created_by_id' => auth()->id(),
                                ]);
                                app(PublishConsentTypeAction::class)->execute($record, $version);
                            });
                            Notification::make()->title('Consentimiento publicado correctamente')->success()->send();
                        } catch (ValidationException $e) {
                            $message = collect($e->errors())->flatten()->first() ?? 'Error al publicar';
                            Notification::make()->title($message)->danger()->send();
                        }
                    }),
                Action::make('publicar_nueva_version')
                    ->label('Nueva versión')
                    ->icon(Heroicon::ArrowPath)
                    ->color('warning')
                    ->visible(fn (ConsentType $record): bool => $record->status === ConsentTypeStatus::Published)
                    ->modalHeading('Publicar nueva versión')
                    ->schema([
                        Textarea::make('legal_text')
                            ->label('Texto legal')
                            ->rows(6)
                            ->required(),
                        TextInput::make('summary')
                            ->label('Resumen de cambios')
                            ->maxLength(255),
                        DatePicker::make('effective_from')
                            ->label('Vigente desde'),
                    ])
                    ->action(function (ConsentType $record, array $data): void {
                        try {
                            /** @var User|null $createdBy */
                            $createdBy = auth()->user();
                            app(PublishNewConsentVersionAction::class)->execute(
                                type: $record,
                                legalText: $data['legal_text'],
                                summary: $data['summary'] ?? null,
                                effectiveFrom: isset($data['effective_from']) ? Carbon::parse($data['effective_from']) : null,
                                createdBy: $createdBy,
                            );
                            Notification::make()->title('Nueva versión publicada correctamente')->success()->send();
                        } catch (ValidationException $e) {
                            $message = collect($e->errors())->flatten()->first() ?? 'Error al publicar';
                            Notification::make()->title($message)->danger()->send();
                        }
                    }),
                Action::make('exportar_estado')
                    ->label('Exportar estado')
                    ->icon(Heroicon::ArrowDownTray)
                    ->color('gray')
                    ->action(fn (ConsentType $record) => Excel::download(
                        new ConsentStatusExport($record),
                        'consentimiento-'.str($record->name)->slug().'-estado.xlsx',
                    )),
                Action::make('exportar_historico')
                    ->label('Exportar histórico')
                    ->icon(Heroicon::Clock)
                    ->color('gray')
                    ->action(fn (ConsentType $record) => Excel::download(
                        new ConsentHistoryExport($record),
                        'consentimiento-'.str($record->name)->slug().'-historico.xlsx',
                    )),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
