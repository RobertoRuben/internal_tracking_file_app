<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChargeBookResource\Pages;
use App\Models\ChargeBook;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class ChargeBookResource extends Resource
{
    protected static ?string $model = ChargeBook::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $modelLabel = 'Cuaderno de Cargos';

    protected static ?string $pluralModelLabel = 'Cuaderno de Cargos';

    protected static ?string $navigationGroup = 'Gestión documental';
    
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del documento')
                    ->description('Seleccione el documento recibido')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\Select::make('document_id')
                            ->label('Documento')
                            ->options(function (callable $get, ?ChargeBook $record, string $operation) {
                                $userDepartmentId = Auth::user()->employee->department_id ?? null;
                                
                                if (!$userDepartmentId) {
                                    return [];
                                }
                                
                                if ($operation === 'edit' && $record) {
                                    return \App\Models\Document::query()
                                        ->where(function($query) use ($userDepartmentId) {
                                            $query->whereHas('derivations', function ($query) use ($userDepartmentId) {
                                                $query->where('destination_department_id', $userDepartmentId);
                                            });
                                        })
                                        ->orWhere('id', $record->document_id)
                                        ->get()
                                        ->mapWithKeys(function ($document) {
                                            return [$document->id => "{$document->doc_code} - {$document->name}"];
                                        })
                                        ->toArray();
                                } else {
                                    return \App\Models\Document::query()
                                        ->whereHas('derivations', function ($query) use ($userDepartmentId) {
                                            $query->where('destination_department_id', $userDepartmentId);
                                        })
                                        ->whereDoesntHave('chargeBooks', function ($query) use ($userDepartmentId) {
                                            $query->where('department_id', $userDepartmentId);
                                        })
                                        ->get()
                                        ->mapWithKeys(function ($document) {
                                            return [$document->id => "{$document->doc_code} - {$document->name}"];
                                        })
                                        ->toArray();
                                }
                            })
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $document = \App\Models\Document::find($state);
                                    $userDepartmentId = Auth::user()->employee->department_id ?? null;
                                    
                                    if ($document && $userDepartmentId) {
                                        $derivation = \App\Models\Derivation::where('document_id', $document->id)
                                            ->where('destination_department_id', $userDepartmentId)
                                            ->latest()
                                            ->first();
                                        
                                        if ($derivation) {
                                            $set('sender_department_id', $derivation->origin_department_id);
                                            $set('sender_user_id', $derivation->derivated_by_user_id);
                                        }
                                    }
                                }
                            })
                            ->validationMessages([
                                'required' => 'Debe seleccionar un documento.'
                            ]),
                    ]),
                Forms\Components\Section::make('Información del remitente')
                    ->description('Datos de quien envió el documento')
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        Forms\Components\Hidden::make('sender_department_id')
                            ->required(),
                        Forms\Components\Hidden::make('sender_user_id')
                            ->required(),
                        Forms\Components\Placeholder::make('sender_department_name')
                            ->label('Origen')
                            ->content(function (callable $get) {
                                $departmentId = $get('sender_department_id');
                                if (!$departmentId) {
                                    return 'Se asignará automáticamente al seleccionar un documento';
                                }
                                
                                $department = \App\Models\Department::find($departmentId);
                                return $department ? $department->name : 'Departamento no encontrado';
                            }),
                        Forms\Components\Placeholder::make('sender_user_name')
                            ->label('Enviado por')
                            ->content(function (callable $get) {
                                $userId = $get('sender_user_id');
                                if (!$userId) {
                                    return 'Se asignará automáticamente al seleccionar un documento';
                                }
                                
                                $user = \App\Models\User::find($userId);
                                return $user ? $user->name : 'Usuario no encontrado';
                            }),
                    ]),
                Forms\Components\Section::make('Observaciones')
                    ->description('Notas adicionales sobre la recepción')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Observaciones')
                            ->placeholder('Ingrese cualquier observación o nota sobre el documento recibido.')
                            ->columnSpanFull(),
                    ]),
                    
                Forms\Components\Hidden::make('receiver_user_id')
                    ->default(fn() => Auth::id()),
                Forms\Components\Hidden::make('department_id')
                    ->default(fn() => Auth::user()->employee->department_id ?? null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('registration_number')
                    ->label('N° de registro')
                    ->formatStateUsing(fn($state) => str_pad($state, 8, '0', STR_PAD_LEFT))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('document.doc_code')
                    ->label('Código de documento')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Código copiado al portapapeles'),
                Tables\Columns\TextColumn::make('document.name')
                    ->label('Nombre del documento')
                    ->limit(30)
                    ->tooltip(fn(ChargeBook $record): string => $record->document->name ?? '')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('senderDepartment.name')
                    ->label('Origen')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('senderUser.name')
                    ->label('Enviado por')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('receiverUser.name')
                    ->label('Recibido por')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de recepción')
                    ->dateTime('d/m/Y H:i')
                    ->timezone('America/Lima')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('sender_department_id')
                    ->label('Departamento de Origen')
                    ->relationship('senderDepartment', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('sender_user_id')
                    ->label('Enviado por')
                    ->relationship('senderUser', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('created_at')
                    ->label('Fecha de recepción')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        
                        if ($data['created_from'] ?? null) {
                            $indicators['created_from'] = 'Registros desde ' . $data['created_from'];
                        }
                        
                        if ($data['created_until'] ?? null) {
                            $indicators['created_until'] = 'Registros hasta ' . $data['created_until'];
                        }
                        
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Ver'),
                    Tables\Actions\EditAction::make()
                        ->label('Editar')
                        ->mutateRecordDataUsing(function (array $data): array {
                            $document = \App\Models\Document::find($data['document_id']);
                            $userDepartmentId = Auth::user()->employee->department_id ?? null;
                            
                            if ($document && $userDepartmentId) {
                                $derivation = \App\Models\Derivation::where('document_id', $document->id)
                                    ->where('destination_department_id', $userDepartmentId)
                                    ->latest()
                                    ->first();
                                
                                if ($derivation) {
                                    $data['sender_department_id'] = $derivation->origin_department_id;
                                    $data['sender_user_id'] = $derivation->derivated_by_user_id;
                                }
                            }
                            
                            return $data;
                        }),
                    Tables\Actions\Action::make('download_document')
                        ->label('Ver documento')
                        ->icon('heroicon-o-document-text')
                        ->color('success')
                        ->url(fn (ChargeBook $record): string => asset('storage/' . $record->document->path))
                        ->openUrlInNewTab()
                        ->visible(fn (ChargeBook $record): bool => $record->document && $record->document->path && file_exists(storage_path('app/public/' . $record->document->path))),
                    Tables\Actions\DeleteAction::make()
                        ->label('Eliminar')
                        ->requiresConfirmation()
                        ->modalHeading('Eliminar registro')
                        ->modalDescription('¿Está seguro que desea eliminar este registro del cuaderno de cargos? Esta acción no se puede deshacer.')
                        ->modalSubmitActionLabel('Sí, eliminar')
                        ->modalCancelActionLabel('No, cancelar')
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Registro eliminado')
                                ->body('El registro ha sido eliminado del cuaderno de cargos.')
                        ),
                ])->tooltip('Acciones')->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados')
                        ->requiresConfirmation()
                        ->modalHeading('Eliminar registros seleccionados')
                        ->modalDescription('¿Está seguro que desea eliminar los registros seleccionados del cuaderno de cargos? Esta acción no se puede deshacer.')
                        ->modalSubmitActionLabel('Sí, eliminar seleccionados')
                        ->modalCancelActionLabel('No, cancelar')
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Registros eliminados')
                                ->body('Los registros seleccionados han sido eliminados del cuaderno de cargos.')
                        ),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageChargeBooks::route('/'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        $userDepartmentId = Auth::user()->employee->department_id ?? null;
        
        if ($userDepartmentId) {
            $query->where('department_id', $userDepartmentId);
        }
        
        return $query;
    }
    
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->can('view_any_charge_book');
    }
}