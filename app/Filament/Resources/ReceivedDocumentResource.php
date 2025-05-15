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

    protected static ?string $modelLabel = 'Inbox';

    protected static ?string $pluralModelLabel = 'Inbox';

    protected static ?string $navigationGroup = 'Gestión documental';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('doc_code')
                    ->label('Código de Documento')
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
                    ->tooltip(fn(Document $record): string => $record->subject)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('creatorDepartment.name')
                    ->label('Origen')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('registeredBy.employee.full_name')
                    ->label('Enviado por')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('derivation_status')
                    ->label('Estado')
                    ->getStateUsing(function (Document $record) {
                        $userDepartmentId = Auth::user()->employee->department_id ?? null;

                        if (!$userDepartmentId) {
                            return 'Sin estado';
                        }

                        $lastDerivation = $record->derivations()
                            ->where('destination_department_id', $userDepartmentId)
                            ->latest()
                            ->first();

                        if (!$lastDerivation) {
                            return 'Sin estado';
                        }

                        $lastDetail = \App\Models\DerivationDetail::where('derivation_id', $lastDerivation->id)
                            ->whereIn('status', ['Recibido', 'Rechazado'])
                            ->latest()
                            ->first();

                        if ($lastDetail) {
                            return $lastDetail->status;
                        }

                        return 'Enviado';
                    })
                    ->colors([
                        'warning' => 'Enviado',
                        'success' => 'Recibido',
                        'danger' => 'Rechazado',
                    ]),
                Tables\Columns\TextColumn::make('derivation_date')
                    ->label('Fecha de envío')
                    ->getStateUsing(function (Document $record) {
                        $userDepartmentId = Auth::user()->employee->department_id ?? null;

                        if (!$userDepartmentId) {
                            return '-';
                        }

                        $derivation = $record->derivations()
                            ->where('destination_department_id', $userDepartmentId)
                            ->latest()
                            ->first();

                        if (!$derivation) {
                            return '-';
                        }

                        return $derivation->created_at->format('d/m/Y H:i');
                    })
                    ->sortable()
                    ->searchable(false),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de creación')
                    ->dateTime('d/m/Y H:i')
                    ->timezone('America/Lima')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Fecha de actualización')                    ->dateTime('d/m/Y H:i')
                    ->timezone('America/Lima')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('derivation_status')
                    ->label('Estado')
                    ->options([
                        'Recibido' => 'Recibido',
                        'Rechazado' => 'Rechazado',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!isset($data['value']) || $data['value'] === '') {
                            return $query;
                        }

                        $userDepartmentId = Auth::user()->employee->department_id ?? null;

                        if (!$userDepartmentId) {
                            return $query;
                        }

                        $status = $data['value'];

                        // Para Recibido o Rechazado, buscamos los que tengan detalles con ese estado específico
                        return $query->whereHas('derivations', function (Builder $derivationQuery) use ($status, $userDepartmentId) {
                            $derivationQuery->where('destination_department_id', $userDepartmentId)
                                ->whereHas('details', function (Builder $detailsQuery) use ($status) {
                                    $detailsQuery->where('status', $status);
                                });
                        });
                    }),
                Tables\Filters\Filter::make('derivation_date')
                    ->label('Fecha de envío')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('date_until')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $userDepartmentId = Auth::user()->employee->department_id ?? null;
                        
                        if (!$userDepartmentId) {
                            return $query;
                        }

                        // Solo aplicamos filtros si hay fechas especificadas
                        if (!isset($data['date_from']) && !isset($data['date_until'])) {
                            return $query;
                        }

                        return $query->whereHas('derivations', function (Builder $derivationQuery) use ($data, $userDepartmentId) {
                            $derivationQuery->where('destination_department_id', $userDepartmentId);
                            
                            if (isset($data['date_from'])) {
                                $derivationQuery->whereDate('created_at', '>=', $data['date_from']);
                            }
                            
                            if (isset($data['date_until'])) {
                                $derivationQuery->whereDate('created_at', '<=', $data['date_until']);
                            }
                        });
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['date_from'] ?? null) {
                            $indicators['date_from'] = 'Enviados desde: ' . $data['date_from'];
                        }

                        if ($data['date_until'] ?? null) {
                            $indicators['date_until'] = 'Enviados hasta: ' . $data['date_until'];
                        }

                        return $indicators;
                    }),
                Tables\Filters\SelectFilter::make('charge_book_status')
                    ->label('Estado en Cuaderno de Cargos')
                    ->options([
                        'registered' => 'Registrados',
                        'not_registered' => 'No Registrados',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!isset($data['value']) || $data['value'] === '') {
                            return $query;
                        }

                        $userDepartmentId = Auth::user()->employee->department_id ?? null;

                        if (!$userDepartmentId) {
                            return $query;
                        }

                        $isRegistered = $data['value'] === 'registered';

                        if ($isRegistered) {
                            // Documentos que están registrados en el cuaderno de cargos del departamento actual
                            return $query->whereHas('chargeBooks', function (Builder $chargeBookQuery) use ($userDepartmentId) {
                                $chargeBookQuery->where('department_id', $userDepartmentId);
                            });
                        } else {
                            // Documentos que NO están registrados en el cuaderno de cargos del departamento actual
                            return $query->whereDoesntHave('chargeBooks', function (Builder $chargeBookQuery) use ($userDepartmentId) {
                                $chargeBookQuery->where('department_id', $userDepartmentId);
                            });
                        }
                    })
            ])->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Ver detalles'),
                    Tables\Actions\Action::make('download')
                        ->label('Ver documento')
                        ->icon('heroicon-o-document-text')
                        ->color('success')
                        ->url(function (Document $record) {
                            return asset('storage/' . $record->path);
                        })
                        ->openUrlInNewTab()
                        ->visible(fn(Document $record) => $record->path && Storage::disk('public')->exists($record->path)),
                    Tables\Actions\Action::make('registerInChargeBook')
                        ->label('Recibir documento')
                        ->icon('heroicon-o-inbox-arrow-down')
                        ->color('primary')->visible(function (Document $record) {
                            $userDepartmentId = Auth::user()->employee->department_id ?? null;

                            if (!$userDepartmentId) {
                                return false;
                            }

                            // Obtener la última derivación dirigida a este departamento
                            $lastDerivation = $record->derivations()
                                ->where('destination_department_id', $userDepartmentId)
                                ->latest()
                                ->first();

                            if (!$lastDerivation) {
                                return false;
                            }

                            // Verificar si ya existe un DerivationDetail con estado Recibido o Rechazado
                            $hasConfirmationDetail = \App\Models\DerivationDetail::where('derivation_id', $lastDerivation->id)
                                ->whereIn('status', ['Recibido', 'Rechazado'])
                                ->exists();

                            // Verificar si ya existe una entrada en el cuaderno de cargos para este documento
                            $isInChargeBook = \App\Models\ChargeBook::where('document_id', $record->id)
                                ->where('department_id', $userDepartmentId)
                                ->exists();

                            // El botón solo debe ser visible si no hay confirmación ni está en cuaderno de cargos
                            return !$hasConfirmationDetail && !$isInChargeBook;
                        })->action(function (Document $record) {
                            $userDepartmentId = Auth::user()->employee->department_id ?? null;

                            if (!$userDepartmentId) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('No tienes un departamento asignado.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $derivation = $record->derivations()
                                ->where('destination_department_id', $userDepartmentId)
                                ->latest()
                                ->first();

                            if (!$derivation) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('No hay una derivación para este documento dirigida a tu departamento.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $hasConfirmationDetail = \App\Models\DerivationDetail::where('derivation_id', $derivation->id)
                                ->whereIn('status', ['Recibido', 'Rechazado'])
                                ->exists();

                            if ($hasConfirmationDetail) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('Este documento ya ha sido recibido o rechazado.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            // Verificar si ya existe una entrada en el cuaderno de cargos
                            $isInChargeBook = \App\Models\ChargeBook::where('document_id', $record->id)
                                ->where('department_id', $userDepartmentId)
                                ->exists();

                            if ($isInChargeBook) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('Este documento ya está registrado en tu cuaderno de cargos.')
                                    ->danger()
                                    ->send();
                                return;
                            }
                            $userName = auth()->user()->name;

                            \App\Models\DerivationDetail::create([
                                'derivation_id' => $derivation->id,
                                'comments' => "El usuario {$userName} confirmó la recepción del documento, y este documento fue registrado en su cuaderno de cargos",
                                'user_id' => auth()->id(),
                                'status' => 'Recibido'
                            ]);

                            \App\Models\ChargeBook::create([
                                'document_id' => $record->id,
                                'sender_department_id' => $derivation->origin_department_id,
                                'sender_user_id' => $derivation->derivated_by_user_id,
                                'receiver_user_id' => auth()->id(),
                                'department_id' => $userDepartmentId,
                                'notes' => "Documento recibido automáticamente desde el sistema de derivaciones"
                            ]);

                            Notification::make()
                                ->title('Éxito')
                                ->body('El documento ha sido recibido y registrado en tu cuaderno de cargos.')
                                ->success()
                                ->send();                        }),
                    Tables\Actions\Action::make('rejectDocument')
                        ->label('Rechazar documento')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Rechazar documento')
                        ->modalDescription('¿Está seguro que desea rechazar este documento? Por favor, ingrese un comentario explicando la razón del rechazo.')
                        ->modalSubmitActionLabel('Sí, rechazar')
                        ->modalCancelActionLabel('No, cancelar')
                        ->form([
                            Forms\Components\Textarea::make('reject_reason')
                                ->label('Razón del rechazo')
                                ->required()
                                ->maxLength(255)
                        ])->visible(function (Document $record) {
                            $userDepartmentId = Auth::user()->employee->department_id ?? null;

                            if (!$userDepartmentId) {
                                return false;
                            }

                            $lastDerivation = $record->derivations()
                                ->where('destination_department_id', $userDepartmentId)
                                ->latest()
                                ->first();

                            // Solo verificamos que exista una derivación
                            return $lastDerivation !== null;
                        })
                        ->action(function (Document $record, array $data) {
                            $userDepartmentId = Auth::user()->employee->department_id ?? null;

                            if (!$userDepartmentId) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('No tienes un departamento asignado.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $derivation = $record->derivations()
                                ->where('destination_department_id', $userDepartmentId)
                                ->latest()
                                ->first();

                            if (!$derivation) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('No hay una derivación para este documento dirigida a tu departamento.')
                                    ->danger()
                                    ->send();
                                return;
                            }                            // Verificamos si ya existe un detalle, pero procedemos igualmente
                            $hasConfirmationDetail = \App\Models\DerivationDetail::where('derivation_id', $derivation->id)
                                ->whereIn('status', ['Recibido', 'Rechazado'])
                                ->exists();

                            // Registramos el nuevo detalle de rechazo
                            $userName = auth()->user()->name;

                            \App\Models\DerivationDetail::create([
                                'derivation_id' => $derivation->id,
                                'comments' => "El usuario {$userName} rechazó la recepción del documento. Razón: " . $data['reject_reason'],
                                'user_id' => auth()->id(),
                                'status' => 'Rechazado'
                            ]);

                            // Eliminamos el registro del cuaderno de cargos si existe
                            $chargeBookEntry = \App\Models\ChargeBook::where('document_id', $record->id)
                                ->where('department_id', $userDepartmentId)
                                ->first();

                            if ($chargeBookEntry) {
                                $chargeBookEntry->delete();
                            }                            Notification::make()
                                ->title('Documento rechazado')
                                ->body('El documento ha sido rechazado con éxito. Si existía un registro en el cuaderno de cargos, ha sido eliminado.')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\Action::make('deriveDocument')
                        ->label('Derivar documento')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('primary')
                        ->modalHeading('Derivar documento')
                        ->modalWidth('lg')
                        ->modalDescription('Seleccione el departamento al que desea derivar este documento.')                        ->form([                            
                            Forms\Components\Select::make('destination_department_id')
                                ->label('Departamento de destino')
                                ->options(function () {
                                    // Obtener todos los departamentos excepto el del usuario actual
                                    $userDepartmentId = Auth::user()->employee->department_id ?? 0;
                                    return \App\Models\Department::where('id', '!=', $userDepartmentId)
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->toArray();
                                })
                                ->required()
                                ->searchable()
                                ->preload()
                                ->placeholder('Seleccione un departamento'),
                            Forms\Components\Textarea::make('comments')
                                ->label('Comentarios')
                                ->placeholder('Agregue comentarios adicionales si es necesario')
                                ->maxLength(255)
                        ])
                        ->visible(function (Document $record) {
                            $userDepartmentId = Auth::user()->employee->department_id ?? null;
                            
                            if (!$userDepartmentId) {
                                return false;
                            }
                            
                            // El botón debe ser visible si el documento está en el cuaderno de cargos del usuario
                            $isInChargeBook = \App\Models\ChargeBook::where('document_id', $record->id)
                                ->where('department_id', $userDepartmentId)
                                ->exists();
                                
                            return $isInChargeBook;
                        })
                        ->action(function (Document $record, array $data) {
                            $userDepartmentId = Auth::user()->employee->department_id ?? null;
                            
                            if (!$userDepartmentId) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('No tienes un departamento asignado.')
                                    ->danger()
                                    ->send();
                                return;
                            }
                            
                            // Crear una nueva derivación
                            $derivation = \App\Models\Derivation::create([
                                'document_id' => $record->id,
                                'origin_department_id' => $userDepartmentId,
                                'destination_department_id' => $data['destination_department_id'],
                                'derivated_by_user_id' => auth()->id(),
                            ]);
                            
                            // Crear el detalle de la derivación (Enviado)
                            $userName = auth()->user()->name;
                            $comments = !empty($data['comments']) 
                                ? "El usuario {$userName} derivó el documento con el comentario: " . $data['comments']
                                : "El usuario {$userName} derivó el documento.";
                                
                            \App\Models\DerivationDetail::create([
                                'derivation_id' => $derivation->id,
                                'comments' => $comments,
                                'user_id' => auth()->id(),
                                'status' => 'Enviado'
                            ]);
                            
                            Notification::make()
                                ->title('Documento derivado')
                                ->body('El documento ha sido derivado con éxito al departamento seleccionado.')
                                ->success()
                                ->send();
                        }),
                ])->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        $userDepartmentId = Auth::user()->employee->department_id ?? null;

        if (!$userDepartmentId) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return parent::getEloquentQuery()
            ->whereHas('derivations', function (Builder $query) use ($userDepartmentId) {
                $query->where('destination_department_id', $userDepartmentId);
            });
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageReceivedDocuments::route('/'),
        ];
    }
}
