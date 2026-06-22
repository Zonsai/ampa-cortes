<?php

namespace App\Filament\Resources\Announcements\Pages;

use App\Filament\Resources\Announcements\AnnouncementResource;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Services\AuditLogger;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAnnouncement extends EditRecord
{
    protected static string $resource = AnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->after(function (Announcement $record): void {
                    app(AuditLogger::class)->log(
                        AuditLog::ANNOUNCEMENT_DELETED,
                        $record,
                        'Anuncio borrado',
                        ['title' => $record->title, 'slug' => $record->slug],
                        subjectLabel: $record->title,
                    );
                }),
        ];
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();

        app(AuditLogger::class)->log(
            AuditLog::ANNOUNCEMENT_UPDATED,
            $record,
            'Anuncio editado',
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
