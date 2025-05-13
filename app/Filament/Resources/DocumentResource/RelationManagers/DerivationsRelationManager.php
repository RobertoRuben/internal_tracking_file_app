<?php

namespace App\Filament\Resources\DocumentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DerivationsRelationManager extends RelationManager
{
    protected static string $relationship = 'derivations';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $title = 'Derivaciones del documento';    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('origin_department_id')
                    ->label('Departamento de origen')
                    ->relationship('originDepartment', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->placeholder('Seleccione un departamento'),
                Forms\Components\Select::make('destination_department_id')
                    ->label('Departamento de destino')
                    ->relationship('destinationDepartment', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->placeholder('Seleccione un departamento'),
                Forms\Components\TextInput::make('status')
                    ->label('Estado')
                    ->default('Pendiente')
                    ->required(),
                Forms\Components\Hidden::make('derivated_by_user_id')
                    ->default(fn () => auth()->id())
                    ->required(),
            ]);
    }    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('originDepartment.name')
                    ->label('Departamento de origen')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('destinationDepartment.name')
                    ->label('Departamento de destino')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pendiente' => 'warning',
                        'Recibido' => 'success',
                        'Rechazado' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('derivatedBy.name')
                    ->label('Derivado por')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de derivación')
                    ->dateTime('d/m/Y H:i')
                    ->timezone('America/Lima')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Crear derivación'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver'),
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados'),
                ]),
            ]);
    }
}
