<?php

namespace App\Filament\Resources\ExtracurricularActivities\RelationManagers;

use App\Actions\Enrollments\CancelEnrollmentAction;
use App\Actions\Enrollments\DropEnrollmentAction;
use App\Actions\Enrollments\PromoteFromWaitlistAction;
use App\Enums\EnrollmentStatus;
use App\Models\ActivityGroup;
use App\Models\Enrollment;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    protected static ?string $title = 'Inscripciones';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('student.last_name')
                    ->label('Alumno/a')
                    ->formatStateUsing(fn ($record) => $record->student->last_name.', '.$record->student->first_name)
                    ->searchable(['students.last_name', 'students.first_name']),
                TextColumn::make('family.name')
                    ->label('Familia')
                    ->searchable(),
                TextColumn::make('activityGroup.name')
                    ->label('Grupo')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('amount')
                    ->label('Importe')
                    ->money('EUR'),
                TextColumn::make('registered_at')
                    ->label('Fecha solicitud')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('waitlist_position')
                    ->label('Pos. espera'),
            ])
            ->defaultSort('registered_at', 'desc')
            ->filters([
                SelectFilter::make('activity_group_id')
                    ->label('Grupo')
                    ->options(fn (): array => ActivityGroup::query()
                        ->where('activity_id', $this->ownerRecord->id)
                        ->pluck('name', 'id')
                        ->all()
                    ),
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(EnrollmentStatus::class),
            ])
            ->recordActions([
                Action::make('drop')
                    ->label('Dar de baja')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Enrollment $record) => in_array($record->status, [
                        EnrollmentStatus::Enrolled,
                        EnrollmentStatus::PendingPayment,
                        EnrollmentStatus::Paid,
                        EnrollmentStatus::Pending,
                    ]))
                    ->action(function (Enrollment $record) {
                        try {
                            app(DropEnrollmentAction::class)->execute($record);
                            $waitlistCount = Enrollment::where('activity_group_id', $record->activity_group_id)
                                ->where('status', EnrollmentStatus::Waitlist)
                                ->count();
                            if ($waitlistCount > 0) {
                                Notification::make()
                                    ->title('Hay alumnos/as en lista de espera')
                                    ->body("Hay {$waitlistCount} ".($waitlistCount === 1 ? 'alumno/a' : 'alumnos/as').' en lista de espera. Usa "Promover siguiente" para asignar la plaza manualmente.')
                                    ->info()
                                    ->persistent()
                                    ->send();
                            }
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->title('No se pudo dar de baja')
                                ->body(collect($e->errors())->flatten()->first())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('cancel')
                    ->label('Cancelar')
                    ->icon('heroicon-o-no-symbol')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Enrollment $record) => in_array($record->status, [
                        EnrollmentStatus::Pending,
                        EnrollmentStatus::Waitlist,
                    ]))
                    ->action(function (Enrollment $record) {
                        try {
                            app(CancelEnrollmentAction::class)->execute($record);
                            $waitlistCount = Enrollment::where('activity_group_id', $record->activity_group_id)
                                ->where('status', EnrollmentStatus::Waitlist)
                                ->count();
                            if ($waitlistCount > 0) {
                                Notification::make()
                                    ->title('Hay alumnos/as en lista de espera')
                                    ->body("Hay {$waitlistCount} ".($waitlistCount === 1 ? 'alumno/a' : 'alumnos/as').' en lista de espera. Usa "Promover siguiente" para asignar la plaza manualmente.')
                                    ->info()
                                    ->persistent()
                                    ->send();
                            }
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->title('No se pudo cancelar la inscripción')
                                ->body(collect($e->errors())->flatten()->first())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('promote')
                    ->label('Cubrir plaza')
                    ->icon('heroicon-o-arrow-up-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Cubrir plaza con lista de espera')
                    ->modalDescription('Se inscribirá automáticamente al siguiente alumno/a de la lista de espera en este grupo, siguiendo el orden de espera.')
                    ->visible(fn (Enrollment $record) => $record->status === EnrollmentStatus::Waitlist)
                    ->action(function (Enrollment $record) {
                        try {
                            app(PromoteFromWaitlistAction::class)->execute($record);
                            Notification::make()
                                ->title('Inscripción promovida')
                                ->success()
                                ->send();
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->title('No se pudo promover')
                                ->body(collect($e->errors())->flatten()->first())
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }
}
