<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DerivationResource\Pages;
use App\Filament\Resources\DerivationResource\RelationManagers;
use App\Models\Derivation;
use App\Models\DerivationDetail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class DerivationResource extends Resource
{
    protected static ?string $model = Derivation::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?string $modelLabel = 'Derivación';

    protected static ?string $pluralModelLabel = 'Derivaciones';

    protected static ?string $navigationGroup = 'Gestión documental';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Estado de la derivación')
                    ->icon('heroicon-o-check-circle')
                    ->description('Información del estado')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'Pendiente' => 'Pendiente',
                                'Recibido' => 'Recibido',
                                'Rechazado' => 'Rechazado',
                            ])
                            ->default('Pendiente')
                            ->required()
                            ->validationMessages([
                                'required' => 'Debe seleccionar un estado.'
                            ]),
                        
                        Forms\Components\Textarea::make('comments')
                            ->label('Observaciones')
                            ->placeholder('Ingrese alguna observación o comentario sobre esta derivación...')
                            ->columnSpanFull(),
                        
                        Forms\Components\Hidden::make('derivated_by_user_id')
                            ->default(fn() => Auth::id())
                            ->required(),
                    ]),

                Forms\Components\Section::make('Información del documento')
                    ->icon('heroicon-o-document-text')
                    ->description('Seleccione el documento a derivar')
                    ->schema([
                        Forms\Components\Select::make('document_id')
                            ->label('Documento')
                            ->relationship(
                                'document', 
                                'name',
                                fn (Builder $query) => $query->where('is_derived', false)
                            )
                            ->getOptionLabelFromRecordUsing(fn($record) => $record->name)
                            ->searchable(['name', 'subject', 'registration_number'])
                            ->preload()
                            ->required()
                            ->validationMessages([
                                'required' => 'Debe seleccionar un documento.'
                            ]),
                    ]),

                Forms\Components\Section::make('Departamentos')
                    ->icon('heroicon-o-building-office-2')
                    ->description('Origen y destino de la derivación')
                    ->schema([
                        Forms\Components\Hidden::make('origin_department_id')
                            ->default(function () {
                                return Auth::user()->employee->department_id ?? null;
                            })
                            ->required(),
                        
                        Forms\Components\Select::make('destination_department_id')
                            ->label('Departamento de destino')
                            ->relationship('destinationDepartment', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->validationMessages([
                                'required' => 'Debe seleccionar un departamento de destino.'
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('document.doc_code')
                    ->label('Código del documento')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Código copiado al portapapeles'),

                Tables\Columns\TextColumn::make('document.name')
                    ->label('Documento')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn(Derivation $record): string => $record->document->name ?? ''),

                Tables\Columns\TextColumn::make('originDepartment.name')
                    ->label('Origen')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('destinationDepartment.name')
                    ->label('Destino')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('derivatedBy.name')
                    ->label('Derivado por')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->colors([
                        'warning' => 'Pendiente',
                        'success' => 'Recibido',
                        'danger' => 'Rechazado',
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de derivación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('origin_department_id')
                    ->label('Departamento de origen')
                    ->relationship('originDepartment', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('destination_department_id')
                    ->label('Departamento de destino')
                    ->relationship('destinationDepartment', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'Pendiente' => 'Pendiente',
                        'Recibido' => 'Recibido',
                        'Rechazado' => 'Rechazado',
                    ]),

                Tables\Filters\SelectFilter::make('derivation_relation')
                    ->label('Relación con derivación')
                    ->options([
                        'sent' => 'Enviadas por mi departamento',
                        'received' => 'Recibidas por mi departamento',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }
                        
                        $userDepartmentId = Auth::user()->employee->department_id ?? null;
                        
                        if (!$userDepartmentId) {
                            return $query;
                        }
                        
                        return match ($data['value']) {
                            'sent' => $query->where('origin_department_id', $userDepartmentId),
                            'received' => $query->where('destination_department_id', $userDepartmentId),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver'),

                Tables\Actions\EditAction::make()
                    ->label('Editar'),
                    
                Tables\Actions\Action::make('changeStatus')
                    ->label('Cambiar estado')
                    ->icon('heroicon-o-check-badge')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('Nuevo estado')
                            ->options([
                                'Pendiente' => 'Pendiente',
                                'Recibido' => 'Recibido',
                                'Rechazado' => 'Rechazado',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('comments')
                            ->label('Observaciones')
                            ->placeholder('Ingrese alguna observación sobre este cambio de estado...'),
                    ])
                    ->action(function (Derivation $record, array $data): void {
                        $record->update(['status' => $data['status']]);
                        
                        if (isset($data['comments']) && !empty($data['comments'])) {
                            \App\Models\DerivationDetail::create([
                                'derivation_id' => $record->id,
                                'comments' => $data['comments'],
                                'user_id' => auth()->id(),
                                'status' => $data['status']
                            ]);
                        }

                        Notification::make()
                            ->title('Estado actualizado')
                            ->body("La derivación ha sido marcada como {$data['status']}.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('changeStatusBulk')
                        ->label('Cambiar estado')
                        ->icon('heroicon-o-check-badge')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('Nuevo estado')
                                ->options([
                                    'Pendiente' => 'Pendiente',
                                    'Recibido' => 'Recibido',
                                    'Rechazado' => 'Rechazado',
                                ])
                                ->required(),
                            Forms\Components\Textarea::make('comments')
                                ->label('Observaciones')
                                ->placeholder('Ingrese alguna observación sobre este cambio de estado...'),
                        ])
                        ->action(function (\Illuminate\Support\Collection $records, array $data): void {
                            $records->each(function ($record) use ($data) {
                                $record->update(['status' => $data['status']]);
                                
                                if (isset($data['comments']) && !empty($data['comments'])) {
                                    \App\Models\DerivationDetail::create([
                                        'derivation_id' => $record->id,
                                        'comments' => $data['comments'],
                                        'user_id' => auth()->id(),
                                        'status' => $data['status']
                                    ]);
                                }
                            });
                            
                            Notification::make()
                                ->title('Estado actualizado')
                                ->body("Las derivaciones seleccionadas han sido marcadas como {$data['status']}.")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados')
                        ->modalHeading('Eliminar derivaciones')
                        ->modalDescription('¿Está seguro que desea eliminar estas derivaciones? Esta acción no se puede deshacer.')
                        ->modalSubmitActionLabel('Sí, eliminar')
                        ->modalCancelActionLabel('No, cancelar')
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Derivaciones eliminadas')
                                ->body('Las derivaciones seleccionadas han sido eliminadas correctamente.')
                        ),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageDerivations::route('/'),
            'view' => Pages\ViewDerivation::route('/{record}'),
            'edit' => Pages\EditDerivation::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['document', 'originDepartment', 'destinationDepartment', 'derivatedBy']);
    }
}