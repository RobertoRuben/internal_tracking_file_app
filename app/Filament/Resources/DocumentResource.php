<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages;
use App\Filament\Resources\DocumentResource\RelationManagers;
use App\Models\Document;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action as NotificationAction;
use App\Filament\Resources\DocumentResource\RelationManagers\DerivationsRelationManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $modelLabel = 'Documento';

    protected static ?string $pluralModelLabel = 'Documentos';

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
                            ->required()
                            ->minLength(3)
                            ->maxLength(255)
                            ->placeholder('Ingrese el nombre del documento')
                            ->validationMessages([
                                'required' => 'El nombre del documento es obligatorio.',
                                'min' => 'El nombre debe tener al menos 3 caracteres.',
                                'max' => 'El nombre no debe exceder los 255 caracteres.'
                            ]),
                        Forms\Components\TextInput::make('pages')
                            ->label('Número de páginas')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(1000)
                            ->placeholder('Ingrese el número de páginas')
                            ->validationMessages([
                                'required' => 'El número de páginas es obligatorio.',
                                'numeric' => 'El número de páginas debe ser numérico.',
                                'min' => 'El documento debe tener al menos 1 página.',
                                'max' => 'El documento no puede exceder las 1000 páginas.'
                            ]),
                    ]),

                Forms\Components\Section::make('Detalles del documento')
                    ->description('Contenido y asunto del documento')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->schema([
                        Forms\Components\Textarea::make('subject')
                            ->label('Asunto')
                            ->required()
                            ->minLength(5)
                            ->maxLength(1000)
                            ->placeholder('Ingrese el asunto del documento')
                            ->validationMessages([
                                'required' => 'El asunto es obligatorio.',
                                'min' => 'El asunto debe tener al menos 5 caracteres.',
                                'max' => 'El asunto no debe exceder los 1000 caracteres.'
                            ])
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('path')
                            ->label('Archivo')
                            ->required()
                            ->disk('public')
                            ->directory('documentos')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(10240)
                            ->openable()
                            ->validationMessages([
                                'required' => 'El archivo es obligatorio.',
                                'max' => 'El archivo no debe exceder los 10MB.',
                                'mimes' => 'El archivo debe ser un PDF.'
                            ])
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Hidden::make('registered_by_user_id')
                    ->default(fn() => Auth::id())
                    ->required(),
                Forms\Components\Hidden::make('created_by_department_id')
                    ->default(function () {
                        return Auth::user()->employee->department_id ?? null;
                    })->required(),

                Forms\Components\Section::make('Información del remitente')
                    ->description('Datos del empleado que remite el documento')
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('Empleado remitente')
                            ->relationship('employee', 'names', fn(Builder $query) => $query->where('is_active', true))
                            ->getOptionLabelFromRecordUsing(fn($record) => "{$record->names} {$record->paternal_surname} {$record->maternal_surname}")
                            ->required()
                            ->searchable(['names', 'paternal_surname', 'maternal_surname', 'dni'])
                            ->preload()
                            ->placeholder('Seleccione un empleado')
                            ->validationMessages([
                                'required' => 'Debe seleccionar un empleado.'
                            ]),
                        Forms\Components\Hidden::make('is_derived')
                            ->default(false),
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
                    ->copyMessage('Código copiado al portapapeles')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('registration_number')
                    ->label('N° de registro')
                    ->formatStateUsing(fn($state) => str_pad($state, 11, '0', STR_PAD_LEFT))
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn(Document $record): string => $record->name),
                Tables\Columns\TextColumn::make('subject')
                    ->label('Asunto')
                    ->searchable()
                    ->limit(30)
                    ->toggleable()
                    ->tooltip(fn(Document $record): string => $record->subject),
                Tables\Columns\TextColumn::make('pages')
                    ->label('Páginas')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('registeredBy.name')
                    ->label('Registrado por')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de registro')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_derived')
                    ->label('Derivado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(fn(bool $state): string => $state ? 'Documento derivado' : 'Documento no derivado'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de registro')
                    ->dateTime('d/m/Y H:i')
                    ->timezone('America/Lima')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Última actualización')
                    ->dateTime('d/m/Y H:i')
                    ->timezone('America/Lima')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('registered_by_user_id')
                    ->label('Registrado por')
                    ->relationship('registeredBy', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_derived')
                    ->label('Estado de derivación')
                    ->placeholder('Todos')
                    ->trueLabel('Derivados')
                    ->falseLabel('No derivados'),
                Tables\Filters\Filter::make('created_at')
                    ->label('Fecha de creación')
                    ->form([
                        Forms\Components\DatePicker::make('desde')
                            ->label('Desde')
                            ->placeholder('Fecha inicial'),
                        Forms\Components\DatePicker::make('hasta')
                            ->label('Hasta')
                            ->placeholder('Fecha final'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $userDepartmentId = Auth::user()->employee->department_id ?? null;
                        
                        if (!$userDepartmentId) {
                            return $query;
                        }
                        
                        $query->where('created_by_department_id', $userDepartmentId);
                        
                        return $query
                            ->when(
                                $data['desde'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['hasta'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Ver'),
                    Tables\Actions\EditAction::make()
                        ->label('Editar'),
                    Tables\Actions\Action::make('download')
                        ->label('Ver documento')
                        ->icon('heroicon-o-document-text')
                        ->color('success')
                        ->url(function (Document $record) {
                            return asset('storage/' . $record->path);
                        })
                        ->openUrlInNewTab()
                        ->visible(fn(Document $record) => $record->path && Storage::disk('public')->exists($record->path)),
                    Tables\Actions\Action::make('derivar')
                        ->label('Derivar')
                        ->icon('heroicon-o-arrow-right-circle')
                        ->color('warning')
                        ->visible(fn (Document $record) => $record->is_derived === false)
                        ->form([
                            Forms\Components\Select::make('destination_department_id')
                                ->label('Departamento de destino')
                                ->relationship('derivations.destinationDepartment', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->validationMessages([
                                    'required' => 'Debe seleccionar un departamento de destino.'
                                ]),
                            Forms\Components\Textarea::make('comments')
                                ->label('Observaciones')
                                ->placeholder('Ingrese alguna observación o comentario sobre esta derivación...')
                                ->columnSpanFull(),
                        ])
                        ->action(function (Document $record, array $data) {
                            $derivation = \App\Models\Derivation::create([
                                'document_id' => $record->id,
                                'origin_department_id' => Auth::user()->employee->department_id ?? null,
                                'destination_department_id' => $data['destination_department_id'],
                                'derivated_by_user_id' => Auth::id(),
                                'status' => 'Enviado',
                            ]);

                            if (isset($data['comments']) && !empty($data['comments'])) {
                                \App\Models\DerivationDetail::create([
                                    'derivation_id' => $derivation->id,
                                    'comments' => $data['comments'] ?? 'Documento derivado',
                                    'user_id' => Auth::id(),
                                    'status' => 'Enviado'
                                ]);
                            }

                            $record->update(['is_derived' => true]);

                            Notification::make()
                                ->success()
                                ->title('Documento derivado')
                                ->body('El documento ha sido derivado correctamente.')
                                ->send();
                        }),
                    Tables\Actions\DeleteAction::make()
                        ->label('Eliminar')
                        ->requiresConfirmation()
                        ->modalHeading('Eliminar documento')
                        ->modalDescription('¿Está seguro que desea eliminar este documento? Esta acción no se puede deshacer y también eliminará el archivo físico asociado.')
                        ->modalSubmitActionLabel('Sí, eliminar')
                        ->modalCancelActionLabel('No, cancelar')->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Documento eliminado')
                                ->body('El documento y su archivo físico han sido eliminados correctamente.')
                        ),
                ])->tooltip('Acciones')->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('downloadMultiple')
                        ->label('Descargar seleccionados')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(function (\Illuminate\Support\Collection $records) {
                            $validRecords = $records->filter(function ($record) {
                                return $record->path && Storage::disk('public')->exists($record->path);
                            });
                            if ($validRecords->count() === 0) {
                                Notification::make()
                                    ->title('Error de descarga')
                                    ->body('No se encontraron archivos válidos para descargar.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            if ($validRecords->count() === 1) {
                                $document = $validRecords->first();
                                $filePath = storage_path('app/public/' . $document->path);

                                if (file_exists($filePath)) {
                                    $filename = 'Documento_' . $document->registration_number . '_' . $document->name . '.pdf';
                                    return response()->download($filePath, $filename, [
                                        'Content-Type' => 'application/pdf',
                                    ]);
                                } else {
                                    Notification::make()
                                        ->title('Error de descarga')
                                        ->body('No se pudo encontrar el archivo para descargar.')
                                        ->danger()
                                        ->send();
                                    return;
                                }
                            }

                            $zipFileName = 'documentos_' . date('Y-m-d_H-i-s') . '.zip';
                            $zipFilePath = storage_path('app/temp/' . $zipFileName);

                            if (!file_exists(storage_path('app/temp'))) {
                                mkdir(storage_path('app/temp'), 0755, true);
                            }

                            $zip = new \ZipArchive();
                            if ($zip->open($zipFilePath, \ZipArchive::CREATE) !== true) {
                                Notification::make()
                                    ->title('Error de descarga')
                                    ->body('No se pudo crear el archivo ZIP.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            foreach ($validRecords as $document) {
                                $filePath = storage_path('app/public/' . $document->path);
                                $relativeName = 'Documento_' . $document->registration_number . '_' . $document->name . '.pdf';

                                if (file_exists($filePath)) {
                                    $zip->addFile($filePath, $relativeName);
                                }
                            }

                            $zip->close();

                            return response()->download($zipFilePath, $zipFileName, [
                                'Content-Type' => 'application/zip',
                            ])->deleteFileAfterSend(true);
                        }),
                    Tables\Actions\BulkAction::make('markAsDerived')
                        ->label('Derivar seleccionados')
                        ->icon('heroicon-o-arrow-right-circle')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('destination_department_id')
                                ->label('Departamento de destino')
                                ->relationship('derivations.destinationDepartment', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->validationMessages([
                                    'required' => 'Debe seleccionar un departamento de destino.'
                                ]),
                            Forms\Components\Textarea::make('comments')
                                ->label('Observaciones')
                                ->placeholder('Ingrese alguna observación o comentario sobre esta derivación...')
                                ->columnSpanFull(),
                        ])
                        ->action(function (\Illuminate\Support\Collection $records, array $data) {
                            $records->each(function ($record) use ($data) {
                                $derivation = \App\Models\Derivation::create([
                                    'document_id' => $record->id,
                                    'origin_department_id' => Auth::user()->employee->department_id ?? null,
                                    'destination_department_id' => $data['destination_department_id'],
                                    'derivated_by_user_id' => Auth::id(),
                                    'status' => 'Enviado',
                                ]);

                                if (isset($data['comments']) && !empty($data['comments'])) {
                                    \App\Models\DerivationDetail::create([
                                        'derivation_id' => $derivation->id,
                                        'comments' => $data['comments'] ?? 'Documento derivado',
                                        'user_id' => Auth::id(),
                                        'status' => 'Enviado'
                                    ]);
                                }

                                $record->update(['is_derived' => true]);
                            });

                            Notification::make()
                                ->success()
                                ->title('Documentos derivados')
                                ->body('Los documentos seleccionados han sido derivados correctamente.')
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados')
                        ->requiresConfirmation()
                        ->modalHeading('Eliminar documentos seleccionados')
                        ->modalDescription('¿Está seguro que desea eliminar los documentos seleccionados? Esta acción no se puede deshacer y también eliminará los archivos físicos asociados.')
                        ->modalSubmitActionLabel('Sí, eliminar seleccionados')
                        ->modalCancelActionLabel('No, cancelar')
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Documentos eliminados')
                                ->body('Los documentos y sus archivos físicos han sido eliminados correctamente.')
                        ),
                ]),
            ]);
    }
    public static function getRelations(): array
    {
        return [
            DerivationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageDocuments::route('/'),
            'view' => Pages\ViewDocument::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $userDepartmentId = Auth::user()->employee->department_id ?? null;

        if ($userDepartmentId) {
            $query->where('created_by_department_id', $userDepartmentId);
        }

        return $query;
    }
}
