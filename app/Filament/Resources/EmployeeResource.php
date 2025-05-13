<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use App\Filament\Resources\EmployeeResource\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\EmployeeResource\RelationManagers\UserRelationManager;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $modelLabel = 'Empleado';

    protected static ?string $pluralModelLabel = 'Empleados';

    protected static ?string $navigationGroup = 'Empresa';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información personal')
                    ->description('Datos personales del empleado')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Forms\Components\TextInput::make('dni')
                            ->label('DNI')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->numeric()
                            ->minLength(8)
                            ->maxLength(8)
                            ->placeholder('Ingrese el DNI')
                            ->validationMessages([
                                'required' => 'El DNI es obligatorio.',
                                'unique' => 'Este DNI ya está registrado.',
                                'numeric' => 'El DNI debe contener solo números.',
                                'min' => 'El DNI debe tener 8 dígitos.',
                                'max' => 'El DNI debe tener 8 dígitos.'
                            ])
                            ->helperText('Ingrese el DNI del empleado - Debe contener 8 dígitos'),
                        Forms\Components\TextInput::make('names')
                            ->label('Nombres')
                            ->required()
                            ->minLength(3)
                            ->maxLength(255)
                            ->placeholder('Ingrese los nombres del empleado')
                            ->regex('/^[a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]+$/')
                            ->validationMessages([
                                'required' => 'El nombre es obligatorio.',
                                'min' => 'El nombre debe tener al menos 3 caracteres.',
                                'max' => 'El nombre no debe exceder los 255 caracteres.',
                                'regex' => 'El nombre solo puede contener letras.'
                            ]),
                        Forms\Components\TextInput::make('paternal_surname')
                            ->label('Apellido Paterno')
                            ->required()
                            ->minLength(3)
                            ->maxLength(255)
                            ->placeholder('Ingrese el apellido paterno')
                            ->regex('/^[a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]+$/')
                            ->validationMessages([
                                'required' => 'El apellido paterno es obligatorio.',
                                'min' => 'El apellido paterno debe tener al menos 3 caracteres.',
                                'max' => 'El apellido paterno no debe exceder los 255 caracteres.',
                                'regex' => 'El apellido paterno solo puede contener letras.'
                            ]),
                        Forms\Components\TextInput::make('maternal_surname')
                            ->label('Apellido Materno')
                            ->required()
                            ->minLength(3)
                            ->maxLength(255)
                            ->placeholder('Ingrese el apellido materno')
                            ->regex('/^[a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]+$/')
                            ->validationMessages([
                                'required' => 'El apellido materno es obligatorio.',
                                'min' => 'El apellido materno debe tener al menos 3 caracteres.',
                                'max' => 'El apellido materno no debe exceder los 255 caracteres.',
                                'regex' => 'El apellido materno solo puede contener letras.'
                            ]),
                    ]),

                Forms\Components\Section::make('Información de contacto')
                    ->description('Datos de contacto del empleado')
                    ->icon('heroicon-o-phone')
                    ->schema([
                        Forms\Components\Select::make('gender')
                            ->label('Género')
                            ->options([
                                'M' => 'Masculino',
                                'F' => 'Femenino'
                            ])
                            ->required()
                            ->validationMessages([
                                'required' => 'Debe seleccionar un género.'
                            ]),
                        Forms\Components\TextInput::make('phone_number')
                            ->label('Número de teléfono')
                            ->tel()
                            ->numeric()
                            ->minLength(9)
                            ->maxLength(9)
                            ->placeholder('Ingrese el número de teléfono')
                            ->regex('/^9\d{8}$/')
                            ->validationMessages([
                                'numeric' => 'El número de teléfono debe contener solo números.',
                                'min' => 'El número de teléfono debe tener 9 dígitos.',
                                'max' => 'El número de teléfono debe tener 9 dígitos.',
                                'regex' => 'El número de teléfono debe comenzar con 9.'
                            ])
                            ->helperText('Ingrese el número de teléfono del empleado - Debe contener 9 dígitos y comenzar con 9'),
                    ]),

                Forms\Components\Section::make('Información laboral')
                    ->description('Datos laborales del empleado')
                    ->icon('heroicon-o-building-office')
                    ->schema([
                        Forms\Components\Select::make('department_id')
                            ->label('Departamento')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->placeholder('Seleccione un departamento')
                            ->validationMessages([
                                'required' => 'Debe seleccionar un departamento.'
                            ]),
                        Forms\Components\Toggle::make('is_active')
                            ->label('¿Empleado activo?')
                            ->default(true)
                            ->helperText('Determina si el empleado está activo en la empresa')
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('dni')
                    ->label('DNI')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('names')
                    ->label('Nombres')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('paternal_surname')
                    ->label('Apellido Paterno')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('maternal_surname')
                    ->label('Apellido Materno')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nombre completo')
                    ->searchable(['names', 'paternal_surname', 'maternal_surname'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('gender')
                    ->label('Género')
                    ->formatStateUsing(fn (string $state): string => $state === 'M' ? 'Masculino' : 'Femenino')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'M' ? 'blue' : 'pink'),
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Teléfono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('department.name')
                    ->label('Departamento')
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
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(fn (bool $state): string => $state ? 'Empleado activo' : 'Empleado inactivo'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('department_id')
                    ->label('Departamento')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('gender')
                    ->label('Género')
                    ->options([
                        'M' => 'Masculino',
                        'F' => 'Femenino'
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver'),
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar')
                    ->requiresConfirmation()
                    ->modalHeading('Eliminar empleado')
                    ->modalDescription('¿Está seguro que desea eliminar este empleado? Esta acción no se puede deshacer.')
                    ->modalSubmitActionLabel('Sí, eliminar')
                    ->modalCancelActionLabel('No, cancelar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados')
                        ->requiresConfirmation()
                        ->modalHeading('Eliminar empleados seleccionados')
                        ->modalDescription('¿Está seguro que desea eliminar los empleados seleccionados? Esta acción no se puede deshacer.')
                        ->modalSubmitActionLabel('Sí, eliminar seleccionados')
                        ->modalCancelActionLabel('No, cancelar'),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activar seleccionados')
                        ->requiresConfirmation()
                        ->modalHeading('Activar empleados seleccionados')
                        ->modalDescription('¿Está seguro que desea activar los empleados seleccionados?')
                        ->modalSubmitActionLabel('Sí, activar')
                        ->modalCancelActionLabel('No, cancelar')
                        ->action(function (Collection $records): void {
                            $records->each(function ($record): void {
                                $record->update(['is_active' => true]);
                            });
                        }),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Desactivar seleccionados')
                        ->requiresConfirmation()
                        ->modalHeading('Desactivar empleados seleccionados')
                        ->modalDescription('¿Está seguro que desea desactivar los empleados seleccionados?')
                        ->modalSubmitActionLabel('Sí, desactivar')
                        ->modalCancelActionLabel('No, cancelar')
                        ->action(function (Collection $records): void {
                            $records->each(function ($record): void {
                                $record->update(['is_active' => false]);
                            });
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            DocumentsRelationManager::class,
            UserRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageEmployees::route('/'),
            'view' => Pages\ViewEmployee::route('/{record}'),
        ];
    }
}
