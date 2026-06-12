<?php

namespace App\Filament\Resources\Forms\Pages;

use App\Filament\Resources\Forms\FormResource;
use App\Models\Form;
use Filament\Resources\Pages\EditRecord;

class EditForm extends EditRecord
{
    protected static string $resource = FormResource::class;

    protected array $pendingTargetItemIds = [];

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
