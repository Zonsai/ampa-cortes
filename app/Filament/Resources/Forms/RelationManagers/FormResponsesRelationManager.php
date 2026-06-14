<?php

namespace App\Filament\Resources\Forms\RelationManagers;

use App\Exports\Forms\FormResponsesExport;
use App\Models\FormField;
use App\Models\FormResponse;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Maatwebsite\Excel\Facades\Excel;

class FormResponsesRelationManager extends RelationManager
{
    protected static string $relationship = 'formResponses';

    protected static ?string $title = 'Respuestas';

    protected static ?string $modelLabel = 'respuesta';

    protected static ?string $pluralModelLabel = 'respuestas';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('submitted_at', 'desc')
            ->columns([
                TextColumn::make('family.name')
                    ->label('Familia')
                    ->searchable(),
                TextColumn::make('student_name')
                    ->label('Alumno/a')
                    ->getStateUsing(fn ($record) => $record->student
                        ? $record->student->last_name.', '.$record->student->first_name
                        : '—'),
                TextColumn::make('submitted_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('answers_count')
                    ->label('Campos respondidos')
                    ->counts('answers'),
            ])
            ->headerActions([
                Action::make('export')
                    ->label('Exportar a Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn () => Excel::download(
                        new FormResponsesExport($this->ownerRecord),
                        'respuestas-'.str($this->ownerRecord->title)->slug().'.xlsx',
                    )),
            ])
            ->recordActions([
                Action::make('ver_respuesta')
                    ->label('Ver respuesta')
                    ->icon(Heroicon::Eye)
                    ->color('gray')
                    ->fillForm(function (FormResponse $record): array {
                        $record->loadMissing(['family.guardians', 'student', 'answers', 'form.formFields']);

                        $guardian = $record->family->guardians->sortBy('id')->first();
                        $lines = [];

                        $lines[] = 'Familia: '.$record->family->name;
                        if ($guardian) {
                            $lines[] = 'Tutor/a: '.$guardian->full_name;
                            if ($guardian->email) {
                                $lines[] = 'Email: '.$guardian->email;
                            }
                            if ($guardian->phone) {
                                $lines[] = 'Teléfono: '.$guardian->phone;
                            }
                        }
                        if ($record->student) {
                            $lines[] = 'Alumno/a: '.$record->student->last_name.', '.$record->student->first_name;
                        }
                        $lines[] = 'Fecha: '.$record->submitted_at->format('d/m/Y H:i');
                        $lines[] = '';

                        $record->form->formFields
                            ->filter(fn (FormField $field) => $field->type->storesAnswer())
                            ->sortBy('sort_order')
                            ->each(function (FormField $field) use ($record, &$lines): void {
                                $raw = $record->answers->firstWhere('form_field_id', $field->id)?->value;
                                $lines[] = $field->label.': '.self::formatAnswerValue($raw);
                            });

                        return ['contenido' => implode("\n", $lines)];
                    })
                    ->schema([
                        Textarea::make('contenido')
                            ->label('Detalle de la respuesta')
                            ->rows(16)
                            ->disabled(),
                    ])
                    ->modalHeading(fn (FormResponse $record): string => 'Respuesta — '.$record->family->name)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
            ]);
    }

    /**
     * Formats a raw stored answer value for display.
     * JSON arrays (e.g. from Checkboxes) are joined with ", "; null/empty returns "—".
     */
    public static function formatAnswerValue(?string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE && is_array($decoded)
            ? implode(', ', $decoded)
            : $value;
    }
}
