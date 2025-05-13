<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $modelLabel = 'Usuario';

    protected static ?string $pluralModelLabel = 'Usuarios';

    protected static ?string $navigationGroup = 'Sistema';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del usuario')
                    ->description('Datos personales e información de contacto')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre de usuario')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('Ingrese el nombre de usuario')
                            ->maxLength(255)
                            ->regex('/^.*[0-9].*$/')
                            ->validationMessages([
                                'unique' => 'Este nombre de usuario ya está en uso.',
                                'regex' => 'El nombre de usuario debe contener al menos un número.',
                                'required' => 'El nombre de usuario es obligatorio.',
                                'max' => 'El nombre de usuario no debe exceder los 255 caracteres.'
                            ])
                            ->helperText('El nombre de usuario debe contener al menos un número'),
                        Forms\Components\TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('correo@mda.gob.pe')
                            ->maxLength(255)
                            ->regex('/^[a-zA-Z0-9._%+-]+@mda\.gob\.pe$/')
                            ->validationMessages([
                                'unique' => 'Este correo electrónico ya está en uso.',
                                'regex' => 'El correo debe pertenecer al dominio @mda.gob.pe',
                                'required' => 'El correo electrónico es obligatorio.',
                                'email' => 'El formato del correo electrónico no es válido.',
                                'max' => 'El correo electrónico no debe exceder los 255 caracteres.'
                            ])
                            ->helperText('El correo debe pertenecer al dominio @mda.gob.pe'),
                    ]),

                Forms\Components\Section::make('Seguridad')
                    ->description('Credenciales de acceso')
                    ->icon('heroicon-o-lock-closed')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->required()
                            ->dehydrateStateUsing(fn(string $state): string => Hash::make($state))
                            ->dehydrated(fn(?string $state): bool => filled($state))
                            ->placeholder('Ingrese una contraseña segura')
                            ->minLength(8)
                            ->regex('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/')
                            ->validationMessages([
                                'regex' => 'La contraseña debe tener al menos 8 caracteres, una letra, un número y un carácter especial.',
                                'required' => 'La contraseña es obligatoria.',
                                'min' => 'La contraseña debe tener al menos 8 caracteres.',
                                'max' => 'La contraseña no debe exceder los 255 caracteres.'
                            ])
                            ->helperText('La contraseña debe tener al menos 8 caracteres, un número y un carácter especial')
                            ->maxLength(255),
                    ]),

                Forms\Components\Section::make('Configuración')
                    ->description('Opciones y preferencias del usuario')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('Empleado')
                            ->relationship(
                                'employee',
                                fn($query) => $query->where('is_active', true)
                            )
                            ->getOptionLabelFromRecordUsing(fn(Employee $record) => "{$record->names} {$record->paternal_surname} {$record->maternal_surname}")
                            ->searchable(['names', 'paternal_surname', 'maternal_surname'])
                            ->placeholder('Seleccione un empleado')
                            ->preload()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'unique' => 'Este empleado ya cuenta con un usuario.',
                                'required' => 'Debe seleccionar un empleado.'
                            ]),
                        Forms\Components\Toggle::make('is_active')
                            ->label('¿Usuario activo?')
                            ->helperText('Determina si el usuario puede acceder al sistema')
                            ->default(true)
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID Usuario')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre de usuario')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Correo electrónico')
                    ->searchable(),
                Tables\Columns\TextColumn::make('employee.names')
                    ->label('Nombre del empleado')
                    ->formatStateUsing(
                        fn($record) =>
                        $record->employee ? "{$record->employee->names} {$record->employee->paternal_surname} {$record->employee->maternal_surname}" : '-'
                    )
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de creación')
                    ->dateTime('d/m/Y H:i')
                    ->timezone('America/Lima')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Fecha de actualización')
                    ->dateTime('d/m/Y H:i')
                    ->timezone('America/Lima')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Estado')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Estado del usuario')
                    ->options([
                        '1' => 'Activos',
                        '0' => 'Inactivos',
                    ])
                    ->default('1')
                    ->query(function (Builder $query, array $data) {
                        return $query->when(
                            $data['value'] !== null,
                            fn(Builder $query) => $query->where('is_active', $data['value'])
                        );
                    }),
                Tables\Filters\SelectFilter::make('employee_id')
                    ->label('Empleado')
                    ->relationship('employee', 'names', fn(Builder $query) => $query->where('is_active', true))
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver'),
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar')
                    ->requiresConfirmation()
                    ->modalHeading('Eliminar usuario')
                    ->modalDescription('¿Está seguro que desea eliminar este usuario? Esta acción no se puede deshacer.')
                    ->modalSubmitActionLabel('Sí, eliminar')
                    ->modalCancelActionLabel('No, cancelar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados')
                        ->requiresConfirmation()
                        ->modalHeading('Eliminar usuarios seleccionados')
                        ->modalDescription('¿Está seguro que desea eliminar los usuarios seleccionados? Esta acción no se puede deshacer.')
                        ->modalSubmitActionLabel('Sí, eliminar')
                        ->modalCancelActionLabel('No, cancelar'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageUsers::route('/'),
            'view' => Pages\ViewUser::route('/{record}'),
        ];
    }
}
