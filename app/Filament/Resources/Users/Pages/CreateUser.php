<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\AuditLog;
use App\Models\Guardian;
use App\Services\AuditLogger;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /** @var array<string> */
    private array $pendingRoles = [];

    private ?int $pendingGuardianId = null;

    private bool $pendingEmailVerified = true;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingRoles = $data['roles_input'] ?? [];
        $this->pendingGuardianId = isset($data['guardian_id']) ? (int) $data['guardian_id'] : null;
        $this->pendingEmailVerified = $data['is_email_verified'] ?? true;

        unset($data['roles_input'], $data['guardian_id'], $data['is_email_verified'], $data['password_confirmation']);

        $data['email_verified_at'] = $this->pendingEmailVerified ? now() : null;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->syncRoles($this->pendingRoles);

        if ($this->pendingGuardianId) {
            Guardian::where('id', $this->pendingGuardianId)
                ->whereNull('user_id')
                ->update(['user_id' => $this->record->id]);
        }

        app(AuditLogger::class)->log(
            AuditLog::USER_CREATED,
            $this->record,
            'Usuario creado desde el panel',
            [
                'user' => $this->record->name,
                'email' => $this->record->email,
                'roles' => $this->record->getRoleNames()->all(),
            ],
            subjectLabel: $this->record->name,
        );
    }

    protected function handleRecordCreation(array $data): Model
    {
        $actor = auth()->user();

        // junta_ampa cannot assign super_admin
        if (! $actor?->hasRole('super_admin')) {
            $this->pendingRoles = array_filter(
                $this->pendingRoles,
                fn ($role) => $role !== 'super_admin'
            );
        }

        return parent::handleRecordCreation($data);
    }
}
