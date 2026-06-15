<?php

namespace App\Filament\Resources\Families\RelationManagers;

use App\Filament\Resources\Forms\RelationManagers\FormResponsesRelationManager as BaseFormResponsesRelationManager;
use App\Models\FormField;
use App\Models\FormResponse;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FormResponsesRelationManager extends RelationManager
{
    protected static string $relationship = 'formResponses';

    protected static ?string $title = 'Respuestas a formularios';

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
                TextColumn::make('form.title')
                    ->label('Formulario')
                    ->searchable(),
                TextColumn::make('student_name')
                    ->label('Alumno/a')
                    ->getStateUsing(fn (FormResponse $record): string => $record->student
                        ? $record->student->last_name.', '.$record->student->first_name
                        : '—'),
                TextColumn::make('submitted_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('answers_count')
                    ->label('Campos respondidos')
                    ->counts('answers'),
            ])
            ->recordActions([
                Action::make('ver_respuesta')
                    ->label('Ver respuesta')
                    ->icon(Heroicon::Eye)
                    ->color('gray')
                    ->fillForm(function (FormResponse $record): array {
                        $record->loadMissing(['student', 'answers', 'form.formFields']);

                        $lines = [];

                        if ($record->student) {
                            $lines[] = 'Alumno/a: '.$record->student->last_name.', '.$record->student->first_name;
                        }

                        if ($record->submitted_at) {
                            $lines[] = 'Fecha: '.$record->submitted_at->format('d/m/Y H:i');
                        }

                        $lines[] = '';

                        $record->form->formFields
                            ->filter(fn (FormField $field) => $field->type->storesAnswer())
                            ->sortBy('sort_order')
                            ->each(function (FormField $field) use ($record, &$lines): void {
                                $raw = $record->answers->firstWhere('form_field_id', $field->id)?->value;
                                $lines[] = $field->label.': '.BaseFormResponsesRelationManager::formatAnswerValue($raw);
                            });

                        return ['contenido' => implode("\n", $lines)];
                    })
                    ->schema([
                        Textarea::make('contenido')
                            ->label('Detalle de la respuesta')
                            ->rows(16)
                            ->disabled(),
                    ])
                    ->modalHeading(fn (FormResponse $record): string => 'Respuesta — '.$record->form->title)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
            ]);
    }
}
