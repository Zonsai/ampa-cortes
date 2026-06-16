<?php

namespace App\Filament\Resources\Guardians\Tables;

use App\Models\Guardian;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class GuardiansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('Tutor/a legal')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['last_name']),
                TextColumn::make('family.name')
                    ->label('Familia')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable(),
                TextColumn::make('relationship')
                    ->label('Relación')
                    ->badge(),
                TextColumn::make('students_count')
                    ->label('Alumnos/as')
                    ->counts('students')
                    ->sortable(),
                IconColumn::make('user_id')
                    ->label('Acceso familiar')
                    ->state(fn (Guardian $record) => $record->user_id !== null)
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray'),
                TextColumn::make('created_at')
                    ->label('Alta')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('last_name')
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('create_family_access')
                    ->label('Crear acceso familiar')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->visible(fn (Guardian $record) => $record->user_id === null
                        && auth()->user()?->hasAnyRole(['super_admin', 'junta_ampa']))
                    ->fillForm(fn (Guardian $record) => [
                        'name' => $record->full_name,
                        'email' => $record->email ?? '',
                        'is_email_verified' => true,
                    ])
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre del usuario')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('password')
                            ->label('Contraseña temporal')
                            ->password()
                            ->required()
                            ->minLength(8)
                            ->same('password_confirmation'),
                        TextInput::make('password_confirmation')
                            ->label('Confirmar contraseña')
                            ->password()
                            ->required()
                            ->dehydrated(false),
                        Toggle::make('is_email_verified')
                            ->label('Marcar email como verificado')
                            ->default(true),
                    ])
                    ->modalHeading('Crear acceso familiar')
                    ->modalSubmitActionLabel('Crear acceso')
                    ->action(function (array $data, Guardian $record): void {
                        if (! auth()->user()?->hasAnyRole(['super_admin', 'junta_ampa'])) {
                            Notification::make()->title('Sin permiso')->danger()->send();

                            return;
                        }

                        if (User::where('email', $data['email'])->exists()) {
                            Notification::make()
                                ->title('Email ya registrado')
                                ->body("Ya existe un usuario con el correo {$data['email']}. Edítalo desde Usuarios y vincúlalo manualmente.")
                                ->danger()
                                ->send();

                            return;
                        }

                        $user = User::create([
                            'name' => $data['name'],
                            'email' => $data['email'],
                            'password' => Hash::make($data['password']),
                            'is_active' => true,
                            'email_verified_at' => ($data['is_email_verified'] ?? true) ? now() : null,
                        ]);

                        $user->assignRole('familia');
                        $record->update(['user_id' => $user->id]);

                        Notification::make()
                            ->title('Acceso familiar creado')
                            ->body("Usuario {$data['email']} creado y vinculado a {$record->full_name}.")
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
