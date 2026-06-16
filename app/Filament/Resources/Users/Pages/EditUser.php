<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\Guardian;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /** @var array<string> */
    private array $pendingRoles = [];

    private ?int $pendingGuardianId = null;

    private bool $pendingEmailVerified = true;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (User $record): void {
                    if ($record->hasRole('super_admin')
                        && User::role('super_admin')->where('is_active', true)->count() <= 1) {
                        Notification::make()
                            ->title('No se puede eliminar')
                            ->body('Este es el último super_admin activo.')
                            ->danger()
                            ->send();
                        $this->halt();
                    }
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Virtual fields are hydrated via afterStateHydrated callbacks in the form.
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingRoles = $data['roles_input'] ?? [];
        $this->pendingGuardianId = isset($data['guardian_id']) ? (int) $data['guardian_id'] : null;
        $this->pendingEmailVerified = $data['is_email_verified'] ?? ($this->record->email_verified_at !== null);

        unset($data['roles_input'], $data['guardian_id'], $data['is_email_verified'], $data['password_confirmation']);

        $data['email_verified_at'] = $this->pendingEmailVerified ? ($this->record->email_verified_at ?? now()) : null;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $record */
        $actor = auth()->user();

        $roles = $this->pendingRoles;

        // junta_ampa cannot assign super_admin
        if (! $actor?->hasRole('super_admin')) {
            $roles = array_filter($roles, fn ($role) => $role !== 'super_admin');
        }

        // The last active super_admin cannot lose the role nor be deactivated.
        if ($record->hasRole('super_admin')
            && $record->is_active
            && User::role('super_admin')->where('is_active', true)->count() <= 1) {
            $willKeepSuperAdmin = in_array('super_admin', $roles, true);
            $willStayActive = ! array_key_exists('is_active', $data) || (bool) $data['is_active'];

            if (! $willKeepSuperAdmin || ! $willStayActive) {
                Notification::make()
                    ->title('Acción no permitida')
                    ->body('Este es el último super_admin activo: no puede perder el rol ni quedar inactivo.')
                    ->danger()
                    ->send();
                $this->halt();
            }
        }

        parent::handleRecordUpdate($record, $data);

        $record->syncRoles($roles);

        // Handle guardian link/delink
        $oldGuardian = Guardian::where('user_id', $record->id)->first();
        $newGuardianId = $this->pendingGuardianId;

        if ($oldGuardian?->id !== $newGuardianId) {
            if ($oldGuardian) {
                $oldGuardian->update(['user_id' => null]);
            }
            if ($newGuardianId) {
                Guardian::where('id', $newGuardianId)
                    ->whereNull('user_id')
                    ->update(['user_id' => $record->id]);
            }
        }

        return $record;
    }
}
