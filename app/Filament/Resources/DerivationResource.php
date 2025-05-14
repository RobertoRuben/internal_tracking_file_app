<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DerivationResource\Pages;
use App\Models\Derivation;
use App\Models\DerivationDetail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
                Forms\Components\Section::make('Información de la derivación')
                    ->icon('heroicon-o-check-circle')
                    ->description('Información general')
                    ->schema([
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
                            ->options(function () {
                                $userDepartmentId = Auth::user()->employee->department_id ?? null;

                                return \App\Models\Document::where('is_derived', false)
                                    ->where('created_by_department_id', $userDepartmentId)
                                    ->get()
                                    ->mapWithKeys(function ($document) {
                                        return [$document->id => "{$document->doc_code} - {$document->name}"];
                                    });
                            })
                            ->disabled(fn(string $operation): bool => $operation === 'edit')
                            ->dehydrated()
                            ->searchable()
                            ->preload()
                            ->required()
                            ->validationMessages([
                                'required' => 'Debe seleccionar un documento.',
                            ]),
                    ]),

                Forms\Components\Section::make('Departamentos')
                    ->icon('heroicon-o-building-office-2')
                    ->description('Origen y destino de la derivación')
                    ->schema([
                        Forms\Components\Hidden::make('origin_department_id')
                            ->default(fn() => Auth::user()->employee->department_id ?? null)
                            ->required(),

                        Forms\Components\Select::make('destination_department_id')
                            ->label('Departamento de destino')
                            ->relationship('destinationDepartment', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->validationMessages([
                                'required' => 'Debe seleccionar un departamento de destino.',
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

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de derivación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('destination_department_id')
                    ->label('Departamento de destino')
                    ->relationship('destinationDepartment', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Fecha desde'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Fecha hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['created_from'] ?? null) {
                            $indicators['created_from'] = 'Derivaciones desde ' . $data['created_from'];
                        }

                        if ($data['created_until'] ?? null) {
                            $indicators['created_until'] = 'Derivaciones hasta ' . $data['created_until'];
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
                        ->modalHeading('Editar derivación')
                        ->modalSubmitActionLabel('Guardar cambios')
                        ->modalCancelActionLabel('Cancelar')
                        ->successNotificationTitle('Derivación actualizada')
                        ->form([
                            Forms\Components\Section::make('Información de la derivación')
                                ->icon('heroicon-o-check-circle')
                                ->description('Información general')
                                ->schema([
                                    Forms\Components\Textarea::make('comments')
                                        ->label('Observaciones')
                                        ->placeholder('Ingrese alguna observación o comentario sobre esta derivación...')
                                        ->columnSpanFull(),

                                    Forms\Components\Hidden::make('derivated_by_user_id')
                                        ->default(fn() => Auth::id()),
                                ]),

                            Forms\Components\Section::make('Información del documento')
                                ->icon('heroicon-o-document-text')
                                ->description('Información del documento seleccionado')
                                ->schema([
                                    Forms\Components\Select::make('document_id')
                                        ->label('Documento')
                                        ->relationship(
                                            'document',
                                            'doc_code',
                                            fn(Builder $query) => $query
                                                ->where('created_by_department_id', Auth::user()->employee->department_id ?? null)
                                        )
                                        ->getOptionLabelFromRecordUsing(fn($record) => "{$record->doc_code} - {$record->name}")
                                        ->dehydrated()
                                        ->searchable()
                                        ->preload(),
                                ]),

                            Forms\Components\Section::make('Departamentos')
                                ->icon('heroicon-o-building-office-2')
                                ->description('Origen y destino de la derivación')
                                ->schema([
                                    Forms\Components\Select::make('origin_department_id')
                                        ->label('Departamento de origen')
                                        ->relationship('originDepartment', 'name')
                                        ->disabled()
                                        ->dehydrated(),
                                    Forms\Components\Select::make('destination_department_id')
                                        ->label('Departamento de destino')
                                        ->relationship('destinationDepartment', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->validationMessages([
                                            'required' => 'Debe seleccionar un departamento de destino.',
                                        ]),
                                ]),
                        ])
                        ->after(function (Derivation $record, array $data) {
                            if ($data['comments'] ?? false) {
                                $user = auth()->user();
                                $destinationDepartment = $record->destinationDepartment;

                                $systemMessage = "Información actualizada por {$user->name}.";

                                \App\Models\DerivationDetail::create([
                                    'derivation_id' => $record->id,
                                    'comments' => $systemMessage . "\n\nObservaciones: {$data['comments']}",
                                    'user_id' => Auth::id(),
                                    'status' => 'Modificado'
                                ]);
                            }
                        }),

                    Tables\Actions\DeleteAction::make()
                        ->label('Eliminar derivación')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Eliminar derivación')
                        ->modalDescription('¿Está seguro que desea eliminar esta derivación? Esta acción no se puede revertir.')
                        ->modalSubmitActionLabel('Sí, eliminar')
                        ->successNotificationTitle('Derivación eliminada correctamente'),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar todas')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Eliminar derivaciones seleccionadas')
                        ->modalDescription('¿Está seguro que desea eliminar las derivaciones seleccionadas? Esta acción no se puede revertir.')
                        ->modalSubmitActionLabel('Sí, eliminar todas')
                        ->successNotificationTitle('Derivaciones eliminadas correctamente'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageDerivations::route('/'),
            'view'  => Pages\ViewDerivation::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['document', 'originDepartment', 'destinationDepartment', 'derivatedBy']);

        $userDepartmentId = Auth::user()->employee->department_id ?? null;

        if ($userDepartmentId) {
            $query->where('origin_department_id', $userDepartmentId);
        }

        return $query;
    }
}
