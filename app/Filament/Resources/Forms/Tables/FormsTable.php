<?php

namespace App\Filament\Resources\Forms\Tables;

use App\Enums\FormStatus;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Form;
use App\Services\AuditLogger;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class FormsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('academicYear.name')
                    ->label('Año')
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('response_scope')
                    ->label('Alcance')
                    ->badge(),
                TextColumn::make('target_type')
                    ->label('Público')
                    ->badge(),
                TextColumn::make('formResponses_count')
                    ->label('Respuestas')
                    ->counts('formResponses'),
                TextColumn::make('opens_at')
                    ->label('Apertura')
                    ->date('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('closes_at')
                    ->label('Cierre')
                    ->date('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('academic_year_id')
                    ->label('Año académico')
                    ->options(AcademicYear::query()->orderByDesc('starts_at')->pluck('name', 'id')),
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(FormStatus::class),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('clonar')
                    ->label('Clonar')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->visible(fn (Form $record): bool => auth()->user()?->can('create', Form::class) ?? false)
                    ->requiresConfirmation()
                    ->modalHeading('Clonar formulario')
                    ->modalDescription('Se creará una copia en estado borrador, con sus campos y su público objetivo. No se copian las respuestas.')
                    ->modalSubmitActionLabel('Clonar')
                    ->action(function (Form $record): void {
                        if (! (auth()->user()?->can('create', Form::class) ?? false)) {
                            Notification::make()->title('Sin permiso')->danger()->send();

                            return;
                        }

                        $clone = DB::transaction(function () use ($record): Form {
                            // Build from fillable only: avoids copying aggregate attributes
                            // (e.g. form_responses_count from the table withCount).
                            $new = new Form($record->only($record->getFillable()));
                            $new->title = $record->title.' (copia)';
                            $new->status = FormStatus::Draft;
                            $new->created_by = auth()->id();
                            // A clone starts as a fresh draft: never inherit scheduling dates.
                            $new->opens_at = null;
                            $new->closes_at = null;
                            $new->save();

                            foreach ($record->formFields as $field) {
                                $fieldClone = $field->replicate();
                                $fieldClone->form_id = $new->id;
                                $fieldClone->save();
                            }

                            foreach ($record->formTargetItems as $target) {
                                $targetClone = $target->replicate();
                                $targetClone->form_id = $new->id;
                                $targetClone->save();
                            }

                            return $new;
                        });

                        app(AuditLogger::class)->log(
                            AuditLog::FORM_CLONED,
                            $clone,
                            'Formulario clonado',
                            ['cloned_from' => $record->title, 'cloned_from_id' => $record->id, 'new_title' => $clone->title],
                            subjectLabel: $clone->title,
                        );

                        Notification::make()
                            ->title('Formulario clonado')
                            ->body("Se ha creado «{$clone->title}» en borrador.")
                            ->success()
                            ->send();
                    }),
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
