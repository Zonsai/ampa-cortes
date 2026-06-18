<?php

namespace App\Filament\Resources\AuditLogs\Pages;

use App\Filament\Resources\AuditLogs\AuditLogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewAuditLog extends ViewRecord
{
    protected static string $resource = AuditLogResource::class;

    // No header actions: logs cannot be edited or deleted from the UI.
    protected function getHeaderActions(): array
    {
        return [];
    }
}
