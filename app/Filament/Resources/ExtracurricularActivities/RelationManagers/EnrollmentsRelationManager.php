<?php

namespace App\Filament\Resources\ExtracurricularActivities\RelationManagers;

use App\Actions\Enrollments\CancelEnrollmentAction;
use App\Actions\Enrollments\ConfirmEnrollmentAction;
use App\Actions\Enrollments\DropEnrollmentAction;
use App\Actions\Enrollments\MarkPendingPaymentAction;
use App\Actions\Enrollments\MoveToWaitlistAction;
use App\Actions\Enrollments\PromoteFromWaitlistAction;
use App\Actions\Enrollments\RegisterPaymentAction;
use App\Actions\Enrollments\VoidPaymentAction;
use App\Enums\EnrollmentStatus;
use App\Enums\PaymentMethod;
use App\Models\ActivityGroup;
use App\Models\Enrollment;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    protected static ?string $title = 'Inscripciones';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

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
                TextColumn::make('price_type')
                    ->label('Tipo precio')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('paid_at')
                    ->label('Fecha pago')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('payment_method')
                    ->label('Método pago')
                    ->formatStateUsing(fn (?PaymentMethod $state): string => $state?->getLabel() ?? '—')
                    ->badge()
                    ->color(fn (?PaymentMethod $state): string => $state?->getColor() ?? 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('registered_at')
                    ->label('Fecha solicitud')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('waitlist_position')
                    ->label('Pos. espera')
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Action::make('confirm_request')
                    ->label('Confirmar solicitud')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Confirmar solicitud de inscripción')
                    ->modalDescription('Se inscribirá al alumno/a en el grupo. Esta acción no se puede deshacer desde el portal familiar.')
                    ->visible(fn (Enrollment $record) => auth()->user()?->can('update', $record) && $record->status === EnrollmentStatus::Pending)
                    ->action(function (Enrollment $record): void {
                        if (! auth()->user()?->can('update', $record)) {
                            Notification::make()->title('Sin permiso')->danger()->send();

                            return;
                        }
                        try {
                            app(ConfirmEnrollmentAction::class)->execute($record);
                            Notification::make()->title('Solicitud confirmada')->success()->send();
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->title('No se pudo confirmar')
                                ->body(collect($e->errors())->flatten()->first())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('register_payment')
                    ->label('Registrar pago')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (Enrollment $record) => auth()->user()?->can('managePayment', $record)
                        && in_array($record->status, [EnrollmentStatus::Enrolled, EnrollmentStatus::PendingPayment]))
                    ->fillForm(fn (): array => ['paid_at' => now()->format('Y-m-d H:i:s')])
                    ->schema([
                        DateTimePicker::make('paid_at')
                            ->label('Fecha y hora del pago')
                            ->required()
                            ->default(now()),
                        Select::make('payment_method')
                            ->label('Método de pago')
                            ->options(PaymentMethod::class)
                            ->required(),
                        Textarea::make('notes_addition')
                            ->label('Notas adicionales (opcional)')
                            ->rows(2),
                    ])
                    ->modalHeading('Registrar pago')
                    ->modalSubmitActionLabel('Registrar pago')
                    ->action(function (array $data, Enrollment $record): void {
                        if (! auth()->user()?->can('managePayment', $record)) {
                            Notification::make()->title('Sin permiso')->danger()->send();

                            return;
                        }
                        try {
                            app(RegisterPaymentAction::class)->execute(
                                $record,
                                $data['paid_at'],
                                PaymentMethod::from($data['payment_method']),
                                $data['notes_addition'] ?? null,
                            );
                            Notification::make()->title('Pago registrado')->success()->send();
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->title('No se pudo registrar el pago')
                                ->body(collect($e->errors())->flatten()->first())
                                ->danger()
                                ->send();
                        }
                    }),

                ActionGroup::make([
                    Action::make('move_to_waitlist')
                        ->label('Enviar a lista de espera')
                        ->icon('heroicon-o-queue-list')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Enviar a lista de espera')
                        ->modalDescription('La solicitud pasará a lista de espera en lugar de confirmarse.')
                        ->visible(fn (Enrollment $record) => auth()->user()?->can('update', $record) && $record->status === EnrollmentStatus::Pending)
                        ->action(function (Enrollment $record): void {
                            if (! auth()->user()?->can('update', $record)) {
                                Notification::make()->title('Sin permiso')->danger()->send();

                                return;
                            }
                            try {
                                app(MoveToWaitlistAction::class)->execute($record);
                                Notification::make()->title('Enviado a lista de espera')->success()->send();
                            } catch (ValidationException $e) {
                                Notification::make()
                                    ->title('No se pudo mover a lista de espera')
                                    ->body(collect($e->errors())->flatten()->first())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('mark_pending_payment')
                        ->label('Pendiente de pago')
                        ->icon('heroicon-o-clock')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Marcar inscripción como pendiente de pago')
                        ->modalDescription('La familia verá la inscripción como pendiente de pago. No se registrará ningún pago todavía.')
                        ->visible(fn (Enrollment $record) => auth()->user()?->can('managePayment', $record)
                            && $record->status === EnrollmentStatus::Enrolled)
                        ->action(function (Enrollment $record): void {
                            if (! auth()->user()?->can('managePayment', $record)) {
                                Notification::make()->title('Sin permiso')->danger()->send();

                                return;
                            }
                            try {
                                app(MarkPendingPaymentAction::class)->execute($record);
                                Notification::make()->title('Marcado como pendiente de pago')->success()->send();
                            } catch (ValidationException $e) {
                                Notification::make()
                                    ->title('No se pudo actualizar')
                                    ->body(collect($e->errors())->flatten()->first())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('void_payment')
                        ->label('Anular pago')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Anular pago')
                        ->modalDescription('Se anulará el pago registrado y la inscripción volverá a pendiente de pago.')
                        ->visible(fn (Enrollment $record) => auth()->user()?->can('managePayment', $record)
                            && $record->status === EnrollmentStatus::Paid)
                        ->action(function (Enrollment $record): void {
                            if (! auth()->user()?->can('managePayment', $record)) {
                                Notification::make()->title('Sin permiso')->danger()->send();

                                return;
                            }
                            try {
                                app(VoidPaymentAction::class)->execute($record);
                                Notification::make()->title('Pago anulado')->success()->send();
                            } catch (ValidationException $e) {
                                Notification::make()
                                    ->title('No se pudo anular el pago')
                                    ->body(collect($e->errors())->flatten()->first())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('drop')
                        ->label('Dar de baja')
                        ->icon('heroicon-o-user-minus')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (Enrollment $record) => auth()->user()?->can('update', $record) && in_array($record->status, [
                            EnrollmentStatus::Enrolled,
                            EnrollmentStatus::PendingPayment,
                            EnrollmentStatus::Paid,
                        ]))
                        ->action(function (Enrollment $record) {
                            if (! auth()->user()?->can('update', $record)) {
                                Notification::make()->title('Sin permiso')->danger()->send();

                                return;
                            }
                            try {
                                app(DropEnrollmentAction::class)->execute($record);
                                $waitlistCount = Enrollment::where('activity_group_id', $record->activity_group_id)
                                    ->where('status', EnrollmentStatus::Waitlist)
                                    ->count();
                                if ($waitlistCount > 0) {
                                    Notification::make()
                                        ->title('Hay alumnos/as en lista de espera')
                                        ->body("Hay {$waitlistCount} ".($waitlistCount === 1 ? 'alumno/a' : 'alumnos/as').' en lista de espera. Usa "Pasar a inscrito/a" para inscribirlos manualmente.')
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
                        ->visible(fn (Enrollment $record) => auth()->user()?->can('update', $record) && in_array($record->status, [
                            EnrollmentStatus::Pending,
                            EnrollmentStatus::Waitlist,
                        ]))
                        ->action(function (Enrollment $record) {
                            if (! auth()->user()?->can('update', $record)) {
                                Notification::make()->title('Sin permiso')->danger()->send();

                                return;
                            }
                            try {
                                app(CancelEnrollmentAction::class)->execute($record);
                                $waitlistCount = Enrollment::where('activity_group_id', $record->activity_group_id)
                                    ->where('status', EnrollmentStatus::Waitlist)
                                    ->count();
                                if ($waitlistCount > 0) {
                                    Notification::make()
                                        ->title('Hay alumnos/as en lista de espera')
                                        ->body("Hay {$waitlistCount} ".($waitlistCount === 1 ? 'alumno/a' : 'alumnos/as').' en lista de espera. Usa "Pasar a inscrito/a" para inscribirlos manualmente.')
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
                        ->label('Pasar a inscrito/a')
                        ->icon('heroicon-o-arrow-up-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Pasar a inscrito/a')
                        ->modalDescription('Se inscribirá al alumno/a de esta fila. Solo se aplica a esta inscripción concreta.')
                        ->visible(fn (Enrollment $record) => auth()->user()?->can('update', $record) && $record->status === EnrollmentStatus::Waitlist)
                        ->action(function (Enrollment $record) {
                            if (! auth()->user()?->can('update', $record)) {
                                Notification::make()->title('Sin permiso')->danger()->send();

                                return;
                            }
                            try {
                                app(PromoteFromWaitlistAction::class)->execute($record);
                                Notification::make()
                                    ->title('Alumno/a inscrito/a correctamente')
                                    ->success()
                                    ->send();
                            } catch (ValidationException $e) {
                                Notification::make()
                                    ->title('No se pudo inscribir')
                                    ->body(collect($e->errors())->flatten()->first())
                                    ->danger()
                                    ->send();
                            }
                        }),
                ]),
            ]);
    }
}
