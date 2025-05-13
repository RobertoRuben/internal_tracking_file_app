<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReceivedDocumentResource\Pages;
use App\Models\Document;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ReceivedDocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $modelLabel = 'Documento recibido';

    protected static ?string $pluralModelLabel = 'Documentos recibidos';

    protected static ?string $navigationGroup = 'Gestión documental';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // No se permite editar estos documentos, pero se necesita el formulario para las vistas
                Forms\Components\Section::make('Información del documento')
                    ->description('Datos básicos del documento')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre del documento')
                            ->disabled(),
                        Forms\Components\TextInput::make('subject')
                            ->label('Asunto')
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('doc_code')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Código copiado al portapapeles'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn(Document $record): string => $record->name),
                Tables\Columns\TextColumn::make('subject')
                    ->label('Asunto')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn(Document $record): string => $record->subject),
                Tables\Columns\TextColumn::make('creatorDepartment.name')
                    ->label('Departamento origen')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Empleado remitente')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('derivation_status')
                    ->label('Estado')
                    ->getStateUsing(function (Document $record) {
                        $userDepartmentId = Auth::user()->employee->department_id ?? null;
                        
                        if (!$userDepartmentId) {
                            return 'Sin estado';
                        }
                        
                        // Obtener la última derivación dirigida a este departamento
                        $lastDerivation = $record->derivations()
                            ->where('destination_department_id', $userDepartmentId)
                            ->latest()
                            ->first();
                            
                        return $lastDerivation ? $lastDerivation->status : 'Sin estado';
                    })
                    ->colors([
                        'warning' => 'Enviado',
                        'success' => 'Recibido',
                        'danger' => 'Rechazado',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de registro')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('origin_department_id')
                    ->label('Departamento de origen')
                    ->relationship('creatorDepartment', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('derivation_status')
                    ->label('Estado de derivación')
                    ->options([
                        'Enviado' => 'Enviado',
                        'Recibido' => 'Recibido',
                        'Rechazado' => 'Rechazado',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }
                        
                        $userDepartmentId = Auth::user()->employee->department_id ?? null;
                        
                        if (!$userDepartmentId) {
                            return $query;
                        }
                        
                        return $query->whereHas('derivations', function (Builder $subQuery) use ($data, $userDepartmentId) {
                            $subQuery->where('destination_department_id', $userDepartmentId)
                                    ->where('status', $data['value']);
                        });
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver'),
                Tables\Actions\Action::make('download')
                    ->label('Ver documento')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->url(function (Document $record) {
                        return asset('storage/' . $record->path);
                    })
                    ->openUrlInNewTab()
                    ->visible(fn(Document $record) => $record->path && Storage::disk('public')->exists($record->path)),
                Tables\Actions\Action::make('receiveDocument')
                    ->label('Recibir')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\Textarea::make('comments')
                            ->label('Observaciones')
                            ->placeholder('Ingrese alguna observación sobre la recepción del documento...')
                    ])
                    ->action(function (Document $record, array $data) {
                        $userDepartmentId = Auth::user()->employee->department_id ?? null;
                        
                        if (!$userDepartmentId) {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('No se pudo determinar su departamento.')
                                ->send();
                            return;
                        }
                        
                        // Buscar la última derivación dirigida a este departamento
                        $derivation = $record->derivations()
                            ->where('destination_department_id', $userDepartmentId)
                            ->where('status', 'Enviado')
                            ->latest()
                            ->first();
                            
                        if (!$derivation) {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('No se encontró una derivación pendiente para este documento.')
                                ->send();
                            return;
                        }
                        
                        // Actualizar el estado de la derivación
                        $derivation->update(['status' => 'Recibido']);
                        
                        // Registrar el comentario
                        \App\Models\DerivationDetail::create([
                            'derivation_id' => $derivation->id,
                            'comments' => $data['comments'] ?? 'Documento recibido',
                            'user_id' => auth()->id(),
                            'status' => 'Recibido'
                        ]);
                        
                        Notification::make()
                            ->success()
                            ->title('Documento recibido')
                            ->body('El documento ha sido marcado como recibido.')
                            ->send();
                    })
                    ->visible(function (Document $record) {
                        $userDepartmentId = Auth::user()->employee->department_id ?? null;
                        
                        if (!$userDepartmentId) {
                            return false;
                        }
                        
                        // Verificar si hay una derivación pendiente
                        return $record->derivations()
                            ->where('destination_department_id', $userDepartmentId)
                            ->where('status', 'Enviado')
                            ->exists();
                    }),
                Tables\Actions\Action::make('registerInChargeBook')
                    ->label('Registrar en cuaderno de cargos')
                    ->icon('heroicon-o-book-open')
                    ->color('primary')
                    ->form([
                        Forms\Components\Select::make('sender_department_id')
                            ->label('Departamento remitente')
                            ->relationship('creatorDepartment', 'name')
                            ->disabled()
                            ->dehydrated(true)
                            ->required(),
                        Forms\Components\Select::make('sender_user_id')
                            ->label('Usuario remitente')
                            ->relationship('creator', 'name')
                            ->disabled()
                            ->dehydrated(true)
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Observaciones')
                            ->placeholder('Ingrese cualquier observación o nota sobre el documento recibido.'),
                    ])
                    ->action(function (Document $record, array $data) {
                        $userDepartmentId = Auth::user()->employee->department_id ?? null;
                        
                        if (!$userDepartmentId) {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('No se pudo determinar su departamento.')
                                ->send();
                            return;
                        }
                        
                        try {
                            // Crear registro en el cuaderno de cargos
                            $chargeBook = \App\Models\ChargeBook::create([
                                'document_id' => $record->id,
                                'sender_department_id' => $data['sender_department_id'],
                                'sender_user_id' => $data['sender_user_id'],
                                'notes' => $data['notes'] ?? null,
                                // Estos campos se asignan automáticamente en el booted del modelo
                                // 'receiver_user_id' => Auth::id(),
                                // 'department_id' => $userDepartmentId,
                                // 'registration_number' => $nextNumber
                            ]);
                            
                            Notification::make()
                                ->success()
                                ->title('Documento registrado')
                                ->body('El documento ha sido registrado correctamente en el cuaderno de cargos con el número: ' . str_pad($chargeBook->registration_number, 8, '0', STR_PAD_LEFT))
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Error al registrar')
                                ->body('No se pudo registrar el documento en el cuaderno de cargos: ' . $e->getMessage())
                                ->send();
                        }
                    })
                    ->visible(function (Document $record) {
                        $userDepartmentId = Auth::user()->employee->department_id ?? null;
                        
                        if (!$userDepartmentId) {
                            return false;
                        }
                        
                        // Verificar si el documento ya está registrado en el cuaderno de cargos de este departamento
                        $existsInChargeBook = \App\Models\ChargeBook::where('document_id', $record->id)
                            ->where('department_id', $userDepartmentId)
                            ->exists();
                        
                        // Verificar si hay una derivación recibida (no solo pendiente)
                        $isReceived = $record->derivations()
                            ->where('destination_department_id', $userDepartmentId)
                            ->where('status', 'Recibido')
                            ->exists();
                        
                        return $isReceived && !$existsInChargeBook;
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Registrar en cuaderno de cargos')
                    ->modalDescription('Al confirmar, este documento será registrado en el cuaderno de cargos de su departamento con un número secuencial automático.')
                    ->modalSubmitActionLabel('Registrar documento')
                    ->modalCancelActionLabel('Cancelar'),
                Tables\Actions\Action::make('rejectDocument')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('comments')
                            ->label('Motivo del rechazo')
                            ->placeholder('Explique el motivo por el que rechaza este documento...')
                            ->required()
                            ->validationMessages([
                                'required' => 'Debe proporcionar un motivo para rechazar el documento.'
                            ]),
                    ])
                    ->action(function (Document $record, array $data) {
                        $userDepartmentId = Auth::user()->employee->department_id ?? null;
                        
                        if (!$userDepartmentId) {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('No se pudo determinar su departamento.')
                                ->send();
                            return;
                        }
                        
                        // Buscar la última derivación dirigida a este departamento
                        $derivation = $record->derivations()
                            ->where('destination_department_id', $userDepartmentId)
                            ->where('status', 'Enviado')
                            ->latest()
                            ->first();
                            
                        if (!$derivation) {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('No se encontró una derivación pendiente para este documento.')
                                ->send();
                            return;
                        }
                        
                        // Actualizar el estado de la derivación
                        $derivation->update(['status' => 'Rechazado']);
                        
                        // Registrar el comentario
                        \App\Models\DerivationDetail::create([
                            'derivation_id' => $derivation->id,
                            'comments' => $data['comments'],
                            'user_id' => auth()->id(),
                            'status' => 'Rechazado'
                        ]);
                        
                        Notification::make()
                            ->success()
                            ->title('Documento rechazado')
                            ->body('El documento ha sido marcado como rechazado.')
                            ->send();
                    })
                    ->visible(function (Document $record) {
                        $userDepartmentId = Auth::user()->employee->department_id ?? null;
                        
                        if (!$userDepartmentId) {
                            return false;
                        }
                        
                        // Verificar si hay una derivación pendiente
                        return $record->derivations()
                            ->where('destination_department_id', $userDepartmentId)
                            ->where('status', 'Enviado')
                            ->exists();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('receiveBulk')
                        ->label('Recibir seleccionados')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->form([
                            Forms\Components\Textarea::make('comments')
                                ->label('Observaciones')
                                ->placeholder('Ingrese alguna observación sobre la recepción de estos documentos...')
                        ])
                        ->action(function (\Illuminate\Support\Collection $records, array $data) {
                            $userDepartmentId = Auth::user()->employee->department_id ?? null;
                            
                            if (!$userDepartmentId) {
                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body('No se pudo determinar su departamento.')
                                    ->send();
                                return;
                            }
                            
                            $count = 0;
                            
                            foreach ($records as $record) {
                                // Buscar la última derivación dirigida a este departamento
                                $derivation = $record->derivations()
                                    ->where('destination_department_id', $userDepartmentId)
                                    ->where('status', 'Enviado')
                                    ->latest()
                                    ->first();
                                    
                                if (!$derivation) {
                                    continue;
                                }
                                
                                // Actualizar el estado de la derivación
                                $derivation->update(['status' => 'Recibido']);
                                
                                // Registrar el comentario
                                \App\Models\DerivationDetail::create([
                                    'derivation_id' => $derivation->id,
                                    'comments' => $data['comments'] ?? 'Documento recibido',
                                    'user_id' => auth()->id(),
                                    'status' => 'Recibido'
                                ]);
                                
                                $count++;
                            }
                            
                            if ($count > 0) {
                                Notification::make()
                                    ->success()
                                    ->title('Documentos recibidos')
                                    ->body("Se han marcado {$count} documentos como recibidos.")
                                    ->send();
                            } else {
                                Notification::make()
                                    ->warning()
                                    ->title('Sin cambios')
                                    ->body('No se encontraron documentos pendientes para recibir.')
                                    ->send();
                            }
                        }),
                        
                    Tables\Actions\BulkAction::make('rejectBulk')
                        ->label('Rechazar seleccionados')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->form([
                            Forms\Components\Textarea::make('comments')
                                ->label('Motivo del rechazo')
                                ->placeholder('Explique el motivo por el que rechaza estos documentos...')
                                ->required()
                                ->validationMessages([
                                    'required' => 'Debe proporcionar un motivo para rechazar los documentos.'
                                ]),
                        ])
                        ->action(function (\Illuminate\Support\Collection $records, array $data) {
                            $userDepartmentId = Auth::user()->employee->department_id ?? null;
                            
                            if (!$userDepartmentId) {
                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body('No se pudo determinar su departamento.')
                                    ->send();
                                return;
                            }
                            
                            $count = 0;
                            
                            foreach ($records as $record) {
                                // Buscar la última derivación dirigida a este departamento
                                $derivation = $record->derivations()
                                    ->where('destination_department_id', $userDepartmentId)
                                    ->where('status', 'Enviado')
                                    ->latest()
                                    ->first();
                                    
                                if (!$derivation) {
                                    continue;
                                }
                                
                                // Actualizar el estado de la derivación
                                $derivation->update(['status' => 'Rechazado']);
                                
                                // Registrar el comentario
                                \App\Models\DerivationDetail::create([
                                    'derivation_id' => $derivation->id,
                                    'comments' => $data['comments'],
                                    'user_id' => auth()->id(),
                                    'status' => 'Rechazado'
                                ]);
                                
                                $count++;
                            }
                            
                            if ($count > 0) {
                                Notification::make()
                                    ->success()
                                    ->title('Documentos rechazados')
                                    ->body("Se han marcado {$count} documentos como rechazados.")
                                    ->send();
                            } else {
                                Notification::make()
                                    ->warning()
                                    ->title('Sin cambios')
                                    ->body('No se encontraron documentos pendientes para rechazar.')
                                    ->send();
                            }
                        }),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $userDepartmentId = Auth::user()->employee->department_id ?? null;
        
        if (!$userDepartmentId) {
            return parent::getEloquentQuery()->whereRaw('1 = 0'); // No mostrar ningún documento
        }
        
        return parent::getEloquentQuery()
            ->whereHas('derivations', function (Builder $query) use ($userDepartmentId) {
                $query->where('destination_department_id', $userDepartmentId);
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReceivedDocuments::route('/'),
            'view' => Pages\ViewReceivedDocument::route('/{record}'),
        ];
    }
}
