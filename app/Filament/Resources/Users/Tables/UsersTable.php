<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Correo electrónico')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->separator(','),
                IconColumn::make('guardian_linked')
                    ->label('Acceso familiar')
                    ->state(fn (User $record) => $record->guardian !== null)
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray'),
                TextColumn::make('guardian.full_name')
                    ->label('Tutor/a vinculado/a')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('guardian.family.name')
                    ->label('Familia')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('email_verified_at')
                    ->label('Email verificado')
                    ->state(fn (User $record) => $record->email_verified_at !== null)
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('role')
                    ->label('Rol')
                    ->options(Role::orderBy('name')->pluck('name', 'name'))
                    ->query(fn (Builder $query, array $data) => $data['value']
                        ? $query->role($data['value'])
                        : $query),
                Filter::make('with_guardian')
                    ->label('Con acceso familiar')
                    ->query(fn (Builder $query) => $query->whereHas('guardian')),
                Filter::make('email_verified')
                    ->label('Email verificado')
                    ->query(fn (Builder $query) => $query->whereNotNull('email_verified_at')),
            ])
            ->recordActions([
                Action::make('reset_password')
                    ->label('Restablecer contraseña')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->visible(fn (User $record) => self::canManageUser($record))
                    ->schema([
                        TextInput::make('password')
                            ->label('Nueva contraseña')
                            ->password()
                            ->required()
                            ->minLength(8)
                            ->same('password_confirmation'),
                        TextInput::make('password_confirmation')
                            ->label('Confirmar contraseña')
                            ->password()
                            ->required()
                            ->dehydrated(false),
                    ])
                    ->modalHeading('Restablecer contraseña')
                    ->modalSubmitActionLabel('Guardar contraseña')
                    ->action(function (array $data, User $record): void {
                        if (! self::canManageUser($record)) {
                            Notification::make()->title('Sin permiso')->danger()->send();

                            return;
                        }
                        $record->update(['password' => $data['password']]);
                        Notification::make()->title('Contraseña restablecida')->success()->send();
                    }),

                Action::make('toggle_active')
                    ->label(fn (User $record) => $record->is_active ? 'Desactivar' : 'Activar')
                    ->icon(fn (User $record) => $record->is_active ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                    ->color(fn (User $record) => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $record) => $record->is_active ? 'Desactivar cuenta' : 'Activar cuenta')
                    ->modalDescription(fn (User $record) => $record->is_active
                        ? 'El usuario no podrá iniciar sesión. Se pueden reactivar en cualquier momento.'
                        : 'El usuario podrá iniciar sesión de nuevo.')
                    ->visible(fn (User $record) => self::canManageUser($record))
                    ->action(function (User $record): void {
                        if (! self::canManageUser($record)) {
                            Notification::make()->title('Sin permiso')->danger()->send();

                            return;
                        }
                        if ($record->is_active && self::isLastActiveSuperAdmin($record)) {
                            Notification::make()
                                ->title('No se puede desactivar')
                                ->body('Este usuario es el último super_admin activo. Crea otro super_admin antes de desactivarlo.')
                                ->danger()
                                ->send();

                            return;
                        }
                        $record->update(['is_active' => ! $record->is_active]);
                        $label = $record->is_active ? 'activada' : 'desactivada';
                        Notification::make()->title("Cuenta {$label}")->success()->send();
                    }),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->hasRole('super_admin'))
                        ->before(function (DeleteBulkAction $action, Collection $records): void {
                            if (self::selectionWouldRemoveLastActiveSuperAdmin($records)) {
                                Notification::make()
                                    ->title('No se puede eliminar')
                                    ->body('La selección incluye al último super_admin activo. El sistema debe conservar al menos uno: no se ha eliminado ningún usuario.')
                                    ->danger()
                                    ->send();
                                $action->halt();
                            }
                        }),
                ]),
            ]);
    }

    private static function canManageUser(User $record): bool
    {
        $actor = auth()->user();
        if (! $actor) {
            return false;
        }
        // junta_ampa cannot manage super_admin users
        if ($record->hasRole('super_admin') && ! $actor->hasRole('super_admin')) {
            return false;
        }

        return $actor->hasAnyRole(['super_admin', 'junta_ampa']);
    }

    private static function isLastActiveSuperAdmin(User $record): bool
    {
        if (! $record->hasRole('super_admin')) {
            return false;
        }

        return User::role('super_admin')->where('is_active', true)->count() <= 1;
    }

    /**
     * Whether deleting every record in the selection would leave the system
     * without a single active super_admin.
     *
     * @param  Collection<int, User>  $records
     */
    private static function selectionWouldRemoveLastActiveSuperAdmin(Collection $records): bool
    {
        $selectedActiveSuperAdmins = $records
            ->filter(fn (User $record) => $record->is_active && $record->hasRole('super_admin'))
            ->count();

        if ($selectedActiveSuperAdmins === 0) {
            return false;
        }

        $totalActiveSuperAdmins = User::role('super_admin')->where('is_active', true)->count();

        return $selectedActiveSuperAdmins >= $totalActiveSuperAdmins;
    }
}
