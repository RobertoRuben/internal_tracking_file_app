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
                            ->options(function () {
                                $userDepartmentId = Auth::user()->employee->department_id ?? null;
                                
                                if (!$userDepartmentId) {
                                    return [];
                                }
                                
                                $derivedDocuments = \App\Models\Document::query()
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
                                
                                return $derivedDocuments;
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
                            ->label('Departamento remitente')
                            ->content(function (callable $get) {
                                $departmentId = $get('sender_department_id');
                                if (!$departmentId) {
                                    return 'Se asignará automáticamente al seleccionar un documento';
                                }
                                
                                $department = \App\Models\Department::find($departmentId);
                                return $department ? $department->name : 'Departamento no encontrado';
                            }),
                        Forms\Components\Placeholder::make('sender_user_name')
                            ->label('Usuario remitente')
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
                    ->searchable(),
                Tables\Columns\TextColumn::make('senderDepartment.name')
                    ->label('Departamento remitente')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('senderUser.name')
                    ->label('Usuario remitente')
                    ->searchable()
                    ->sortable(),
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
                Tables\Filters\SelectFilter::make('document_id')
                    ->label('Documento')
                    ->relationship('document', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('sender_department_id')
                    ->label('Departamento remitente')
                    ->relationship('senderDepartment', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('sender_user_id')
                    ->label('Usuario remitente')
                    ->relationship('senderUser', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver'),
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
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
        
        // Obtener el departamento del usuario actual
        $userDepartmentId = Auth::user()->employee->department_id ?? null;
        
        if ($userDepartmentId) {
            // Filtrar por registros que pertenecen al departamento del usuario actual
            $query->where('department_id', $userDepartmentId);
        }
        
        return $query;
    }
}
