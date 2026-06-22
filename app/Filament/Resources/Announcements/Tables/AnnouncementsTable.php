<?php

namespace App\Filament\Resources\Announcements\Tables;

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementStatus;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Services\AuditLogger;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AnnouncementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_pinned')
                    ->label('Fijado')
                    ->boolean()
                    ->trueIcon('heroicon-s-bookmark')
                    ->falseIcon('heroicon-o-bookmark')
                    ->trueColor('warning')
                    ->falseColor('gray'),
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->limit(60)
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('audience')
                    ->label('Audiencia')
                    ->badge(),
                TextColumn::make('published_at')
                    ->label('Publicación')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label('Caduca')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('author.name')
                    ->label('Autor')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(AnnouncementStatus::class),
                SelectFilter::make('audience')
                    ->label('Audiencia')
                    ->options(AnnouncementAudience::class),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('publicar')
                    ->label('Publicar')
                    ->icon('heroicon-o-megaphone')
                    ->color('success')
                    ->visible(fn (Announcement $record): bool => $record->status !== AnnouncementStatus::Published)
                    ->requiresConfirmation()
                    ->modalHeading('Publicar anuncio')
                    ->modalDescription('El anuncio quedará visible según su audiencia y fechas.')
                    ->action(function (Announcement $record): void {
                        $statusBefore = $record->status;
                        $record->update([
                            'status' => AnnouncementStatus::Published,
                            'published_at' => $record->published_at ?? now(),
                        ]);
                        app(AuditLogger::class)->log(
                            AuditLog::ANNOUNCEMENT_PUBLISHED,
                            $record,
                            'Anuncio publicado',
                            ['title' => $record->title, 'slug' => $record->slug, 'status_before' => $statusBefore->value, 'status_after' => 'published', 'audience' => $record->audience->value],
                            subjectLabel: $record->title,
                        );
                    }),
                Action::make('archivar')
                    ->label('Archivar')
                    ->icon('heroicon-o-archive-box')
                    ->color('warning')
                    ->visible(fn (Announcement $record): bool => $record->status !== AnnouncementStatus::Archived)
                    ->requiresConfirmation()
                    ->modalHeading('Archivar anuncio')
                    ->modalDescription('El anuncio dejará de mostrarse en el front público y la zona familiar.')
                    ->action(function (Announcement $record): void {
                        $statusBefore = $record->status;
                        $record->update(['status' => AnnouncementStatus::Archived]);
                        app(AuditLogger::class)->log(
                            AuditLog::ANNOUNCEMENT_ARCHIVED,
                            $record,
                            'Anuncio archivado',
                            ['title' => $record->title, 'slug' => $record->slug, 'status_before' => $statusBefore->value, 'status_after' => 'archived'],
                            subjectLabel: $record->title,
                        );
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
