<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepartmentResource\Pages;
use App\Filament\Resources\DepartmentResource\RelationManagers;
use App\Models\Department;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $modelLabel = 'Departamento';

    protected static ?string $pluralModelLabel = 'Departamentos';

    protected static ?string $navigationGroup = 'Empresa';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del departamento')
                    ->description('Datos básicos del departamento')
                    ->icon('heroicon-o-building-office')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre del departamento')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('Ingrese el nombre del departamento')
                            ->maxLength(255)
                            ->regex('/^[a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]+$/')
                            ->validationMessages([
                                'unique' => 'Este nombre de departamento ya está en uso.',
                                'required' => 'El nombre del departamento es obligatorio.',
                                'max' => 'El nombre del departamento no debe exceder los 255 caracteres.',
                                'regex' => 'El nombre del departamento solo puede contener letras.'
                            ])
                            ->helperText('Ingrese un nombre descriptivo para el departamento - Solo se permiten letras'),
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
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre del departamento')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('employees_count')
                    ->label('Cantidad de empleados')
                    ->counts('employees')
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
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Ver'),
                    Tables\Actions\EditAction::make()
                        ->label('Editar'),
                    Tables\Actions\DeleteAction::make()
                        ->label('Eliminar')
                        ->requiresConfirmation()
                        ->modalHeading('Eliminar departamento')
                        ->modalDescription('¿Está seguro que desea eliminar este departamento? Esta acción no se puede deshacer y podría afectar a los empleados asociados.')
                        ->modalSubmitActionLabel('Sí, eliminar')
                        ->modalCancelActionLabel('No, cancelar'),
                ])->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados')
                        ->requiresConfirmation()
                        ->modalHeading('Eliminar departamentos seleccionados')
                        ->modalDescription('¿Está seguro que desea eliminar los departamentos seleccionados? Esta acción no se puede deshacer y podría afectar a los empleados asociados.')
                        ->modalSubmitActionLabel('Sí, eliminar')
                        ->modalCancelActionLabel('No, cancelar'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\EmployeesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageDepartments::route('/'),
            'view' => Pages\ViewDepartment::route('/{record}'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->can('view_any_department');
    }
}
