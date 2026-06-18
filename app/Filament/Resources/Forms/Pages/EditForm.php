<?php

namespace App\Filament\Resources\Forms\Pages;

use App\Filament\Resources\Forms\FormResource;
use App\Models\AuditLog;
use App\Models\Form;
use App\Services\AuditLogger;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditForm extends EditRecord
{
    protected static string $resource = FormResource::class;

    protected array $pendingTargetItemIds = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['target_item_ids'] = $this->getRecord()
            ->formTargetItems()
            ->pluck('targetable_id')
            ->toArray();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingTargetItemIds = $data['target_item_ids'] ?? [];
        unset($data['target_item_ids']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->syncTargetItems($this->getRecord());

        $record = $this->getRecord();
        app(AuditLogger::class)->log(
            AuditLog::FORM_UPDATED,
            $record,
            'Formulario editado',
            ['title' => $record->title, 'status' => $record->status?->value],
            subjectLabel: $record->title,
        );
    }

    private function syncTargetItems(Form $record): void
    {
        $record->formTargetItems()->delete();

        $morphClass = $record->target_type->morphClass();

        if ($morphClass === null || empty($this->pendingTargetItemIds)) {
            return;
        }

        foreach ($this->pendingTargetItemIds as $id) {
            $record->formTargetItems()->create([
                'targetable_type' => $morphClass,
                'targetable_id' => $id,
            ]);
        }
    }
}
