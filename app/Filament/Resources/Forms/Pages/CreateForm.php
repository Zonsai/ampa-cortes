<?php

namespace App\Filament\Resources\Forms\Pages;

use App\Filament\Resources\Forms\FormResource;
use App\Models\Form;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateForm extends CreateRecord
{
    protected static string $resource = FormResource::class;

    protected array $pendingTargetItemIds = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingTargetItemIds = $data['target_item_ids'] ?? [];
        unset($data['target_item_ids']);

        $data['created_by'] = Filament::auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->syncTargetItems($this->getRecord());
    }

    private function syncTargetItems(Form $record): void
    {
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
