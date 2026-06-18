<?php

namespace App\Filament\Resources\AuditLogs\Schemas;

use App\Models\AuditLog;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AuditLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Acción')
                ->columns(2)
                ->schema([
                    TextEntry::make('action')
                        ->label('Acción')
                        ->badge()
                        ->formatStateUsing(fn (AuditLog $record): string => $record->actionLabel()),
                    TextEntry::make('created_at')
                        ->label('Fecha')
                        ->dateTime('d/m/Y H:i:s'),
                    TextEntry::make('description')
                        ->label('Descripción')
                        ->placeholder('—')
                        ->columnSpanFull(),
                    TextEntry::make('subject_label')
                        ->label('Sujeto')
                        ->placeholder('—'),
                    TextEntry::make('subject_type')
                        ->label('Tipo de sujeto')
                        ->placeholder('—')
                        ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '—'),
                ]),
            Section::make('Actor')
                ->columns(2)
                ->schema([
                    TextEntry::make('actor_name')->label('Nombre')->placeholder('Sistema'),
                    TextEntry::make('actor_email')->label('Email')->placeholder('—'),
                    TextEntry::make('ip_address')->label('IP')->placeholder('—'),
                    TextEntry::make('user_agent')->label('Navegador')->placeholder('—')->columnSpanFull(),
                ]),
            Section::make('Detalles')
                ->schema([
                    KeyValueEntry::make('properties')
                        ->label('Propiedades (antes/después y metadatos)')
                        ->keyLabel('Campo')
                        ->valueLabel('Valor'),
                ]),
        ]);
    }
}
