<?php

namespace App\Filament\Resources\DerivationResource\Pages;

use App\Filament\Resources\DerivationResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use Filament\Infolists\Components;
use Filament\Forms;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use App\Models\DerivationDetail;
use Illuminate\Support\Facades\Auth;

class ViewDerivation extends ViewRecord
{
    protected static string $resource = DerivationResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return parent::infolist($infolist)
            ->schema([
                Components\Section::make('Información del documento')
                    ->icon('heroicon-o-document-text')
                    ->description('Detalles del documento derivado')
                    ->schema([
                        Components\TextEntry::make('document.name')
                            ->label('Nombre del documento')
                            ->copyable(),
                        Components\TextEntry::make('document.registration_number')
                            ->label('Número de registro')
                            ->formatStateUsing(fn($state) => str_pad($state, 11, '0', STR_PAD_LEFT)),
                        Components\TextEntry::make('document.subject')
                            ->label('Asunto')
                            ->columnSpanFull(),
                    ]),

                Components\Section::make('Información de derivación')
                    ->icon('heroicon-o-arrow-path')
                    ->description('Detalles de la derivación')
                    ->columns(2)
                    ->schema([                        Components\TextEntry::make('originDepartment.name')
                            ->label('Departamento de origen'),
                        Components\TextEntry::make('destinationDepartment.name')
                            ->label('Departamento de destino'),
                        Components\TextEntry::make('derivatedBy.name')
                            ->label('Derivado por'),
                        Components\TextEntry::make('created_at')
                            ->label('Fecha de derivación')
                            ->dateTime('d/m/Y H:i')
                            ->timezone('America/Lima'),
                        Components\TextEntry::make('updated_at')
                            ->label('Última actualización')
                            ->dateTime('d/m/Y H:i')
                            ->timezone('America/Lima'),
                    ]),

                Components\Section::make('Historial de observaciones')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->description('Comentarios sobre la derivación')
                    ->visible(fn ($record) => $record->details()->exists())
                    ->schema([
                        Components\RepeatableEntry::make('details')
                            ->label(false)
                            ->schema([                                Components\TextEntry::make('created_at')
                                    ->label('Fecha')
                                    ->dateTime('d/m/Y H:i'),
                                Components\TextEntry::make('user.name')
                                    ->label('Usuario'),
                                Components\TextEntry::make('comments')
                                    ->label('Comentario')
                                    ->markdown()
                                    ->columnSpanFull(),
                            ])
                            ->columns(3),
                    ]),
            ]);
    }    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Editar'),
        ];
    }
}