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
                Tables\Actions\Action::make('viewDetails')
                    ->label('Ver detalles')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn ($record) => "Detalles de la derivación #{$record->id}")
                    ->modalWidth('xl')
                    ->modalSubmitAction(false)
                    ->modalContent(fn ($record) => view('filament.resources.derivation.details', [
                        'derivation' => $record,
                        'details' => $record->details()->with('user')->latest()->get(),
                        'originDepartment' => $record->originDepartment->name,
                        'destinationDepartment' => $record->destinationDepartment->name,
                        'derivatedBy' => $record->derivatedBy->name,
                        'created_at' => $record->created_at->format('d/m/Y H:i'),
                    ])),                Tables\Actions\EditAction::make()
                    ->label('Editar')
                    ->visible(function (\App\Models\Derivation $record) {
                        // Obtener el último detalle de la derivación
                        $lastDetail = $record->details()->latest()->first();
                        // Mostrar el botón solo si no hay detalles o el último detalle tiene estado "Enviado"
                        return !$lastDetail || $lastDetail->status === 'Enviado';
                    }),
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
