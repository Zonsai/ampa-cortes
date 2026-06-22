<?php

namespace App\Filament\Resources\Announcements\Pages;

use App\Filament\Resources\Announcements\AnnouncementResource;
use App\Models\AuditLog;
use App\Services\AuditLogger;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateAnnouncement extends CreateRecord
{
    protected static string $resource = AnnouncementResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by_id'] = Filament::auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        app(AuditLogger::class)->log(
            AuditLog::ANNOUNCEMENT_CREATED,
            $record,
            'Anuncio creado',
            [
                'title' => $record->title,
                'slug' => $record->slug,
                'status' => $record->status?->value,
                'audience' => $record->audience?->value,
            ],
            subjectLabel: $record->title,
        );
    }
}
