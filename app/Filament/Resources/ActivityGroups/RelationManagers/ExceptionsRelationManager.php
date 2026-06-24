<?php

namespace App\Filament\Resources\ActivityGroups\RelationManagers;

use App\Enums\ExceptionType;
use App\Models\ActivityGroupException;
use App\Models\AuditLog;
use App\Services\AuditLogger;
use App\Support\ActivityGroupExceptionAuditData;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ExceptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'exceptions';

    protected static ?string $title = 'Excepciones';

    protected static ?string $modelLabel = 'excepción';

    protected static ?string $pluralModelLabel = 'excepciones';

    /**
     * Normalizes a raw form state value into an ExceptionType.
     */
    public static function resolveType(mixed $value): ?ExceptionType
    {
        if ($value instanceof ExceptionType) {
            return $value;
        }

        if (is_string($value)) {
            return ExceptionType::tryFrom($value);
        }

        return null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('Tipo')
                    ->options(ExceptionType::class)
                    ->required()
                    ->live(),
                DatePicker::make('original_date')
                    ->label('Fecha original')
                    ->displayFormat('d/m/Y')
                    ->helperText('Debe coincidir con un día recurrente del grupo.')
                    ->visible(fn (Get $get): bool => in_array(self::resolveType($get('type')), [ExceptionType::Cancelled, ExceptionType::Modified]))
                    ->required(fn (Get $get): bool => in_array(self::resolveType($get('type')), [ExceptionType::Cancelled, ExceptionType::Modified])),
                DatePicker::make('new_date')
                    ->label('Nueva fecha')
                    ->displayFormat('d/m/Y')
                    ->visible(fn (Get $get): bool => in_array(self::resolveType($get('type')), [ExceptionType::Modified, ExceptionType::Extra]))
                    ->required(fn (Get $get): bool => self::resolveType($get('type')) === ExceptionType::Extra),
                TimePicker::make('new_starts_at')
                    ->label('Nueva hora inicio')
                    ->seconds(false)
                    ->visible(fn (Get $get): bool => in_array(self::resolveType($get('type')), [ExceptionType::Modified, ExceptionType::Extra]))
                    ->required(fn (Get $get): bool => self::resolveType($get('type')) === ExceptionType::Extra),
                TimePicker::make('new_ends_at')
                    ->label('Nueva hora fin')
                    ->seconds(false)
                    ->visible(fn (Get $get): bool => in_array(self::resolveType($get('type')), [ExceptionType::Modified, ExceptionType::Extra]))
                    ->required(fn (Get $get): bool => self::resolveType($get('type')) === ExceptionType::Extra),
                TextInput::make('new_location')
                    ->label('Nueva ubicación')
                    ->maxLength(150)
                    ->visible(fn (Get $get): bool => in_array(self::resolveType($get('type')), [ExceptionType::Modified, ExceptionType::Extra])),
                Textarea::make('reason')
                    ->label('Motivo')
                    ->rows(2)
                    ->required(fn (Get $get): bool => self::resolveType($get('type')) === ExceptionType::Cancelled),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('original_date', 'desc')
            ->columns([
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('original_date')
                    ->label('Fecha afectada')
                    ->date('d/m/Y')
                    ->placeholder('—'),
                TextColumn::make('new_date')
                    ->label('Nueva fecha')
                    ->date('d/m/Y')
                    ->placeholder('—'),
                TextColumn::make('schedule')
                    ->label('Horario')
                    ->getStateUsing(fn (ActivityGroupException $record): string => $record->new_starts_at && $record->new_ends_at
                        ? substr((string) $record->new_starts_at, 0, 5).' – '.substr((string) $record->new_ends_at, 0, 5)
                        : '—'),
                TextColumn::make('new_location')
                    ->label('Ubicación')
                    ->placeholder('—'),
                TextColumn::make('reason')
                    ->label('Motivo')
                    ->limit(40)
                    ->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->using(function (array $data): Model {
                        $relationship = $this->getRelationship();

                        try {
                            $exception = new ActivityGroupException($data);
                            $relationship->save($exception);

                            app(AuditLogger::class)->log(
                                AuditLog::GROUP_EXCEPTION_CREATED,
                                $exception,
                                'Excepción de grupo creada',
                                ActivityGroupExceptionAuditData::properties($exception),
                                subjectLabel: ActivityGroupExceptionAuditData::label($exception),
                            );

                            return $exception;
                        } catch (\InvalidArgumentException $e) {
                            Notification::make()
                                ->title('No se pudo crear la excepción')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            throw new Halt;
                        }
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->using(function (Model $record, array $data): Model {
                        try {
                            $record->update($data);

                            app(AuditLogger::class)->log(
                                AuditLog::GROUP_EXCEPTION_UPDATED,
                                $record,
                                'Excepción de grupo editada',
                                ActivityGroupExceptionAuditData::properties($record),
                                subjectLabel: ActivityGroupExceptionAuditData::label($record),
                            );

                            return $record;
                        } catch (\InvalidArgumentException $e) {
                            Notification::make()
                                ->title('No se pudo editar la excepción')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            throw new Halt;
                        }
                    }),
                DeleteAction::make()
                    ->using(function (Model $record): bool {
                        app(AuditLogger::class)->log(
                            AuditLog::GROUP_EXCEPTION_DELETED,
                            $record,
                            'Excepción de grupo borrada',
                            ActivityGroupExceptionAuditData::properties($record),
                            subjectLabel: ActivityGroupExceptionAuditData::label($record),
                        );

                        return $record->delete();
                    }),
            ]);
    }
}
