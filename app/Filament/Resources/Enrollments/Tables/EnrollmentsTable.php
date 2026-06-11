<?php

namespace App\Filament\Resources\Enrollments\Tables;

use App\Actions\Enrollments\CancelEnrollmentAction;
use App\Actions\Enrollments\DropEnrollmentAction;
use App\Actions\Enrollments\PromoteFromWaitlistAction;
use App\Enums\EnrollmentStatus;
use App\Models\AcademicYear;
use App\Models\Enrollment;
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
use Illuminate\Validation\ValidationException;

class EnrollmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.last_name')
                    ->label('Alumno/a')
                    ->formatStateUsing(fn ($record) => $record->student->last_name.', '.$record->student->first_name)
                    ->searchable(['students.last_name', 'students.first_name'])
                    ->sortable(),
                TextColumn::make('family.name')
                    ->label('Familia')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('activity.name')
                    ->label('Actividad')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('activityGroup.name')
                    ->label('Grupo')
                    ->sortable(),
                TextColumn::make('academicYear.name')
                    ->label('Año académico')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('amount')
                    ->label('Importe')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('waitlist_position')
                    ->label('Pos. espera')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('registered_at')
                    ->label('Inscrito')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('registered_at', 'desc')
            ->filters([
                SelectFilter::make('academic_year_id')
                    ->label('Año académico')
                    ->options(AcademicYear::query()->orderByDesc('starts_at')->pluck('name', 'id')),
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(EnrollmentStatus::class),
                TrashedFilter::make(),
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
                    ->label('Promover siguiente')
                    ->icon('heroicon-o-arrow-up-circle')
                    ->color('success')
                    ->requiresConfirmation()
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

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->hasRole('super_admin')),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
