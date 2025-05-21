<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;

class ViewDocument extends ViewRecord
{
    protected static string $resource = DocumentResource::class;
    
    public function infolist(Infolist $infolist): Infolist
    {
        return parent::infolist($infolist)
            ->schema([
                Components\Section::make('Información de registro')
                    ->icon('heroicon-o-information-circle')
                    ->description('Detalles de registro')
                    ->aside()
                    ->schema([
                        Components\TextEntry::make('registeredBy.name')
                            ->label('Registrado por'),
                        Components\TextEntry::make('creatorDepartment.name')
                            ->label('Departamento de origen'),
                        Components\TextEntry::make('created_at')
                            ->label('Fecha de registro')
                            ->dateTime('d/m/Y H:i'),
                        Components\TextEntry::make('is_derived')
                            ->label('Estado de derivación')
                            ->formatStateUsing(fn(bool $state) => $state ? 'Derivado' : 'No derivado')
                            ->badge()
                            ->color(fn(bool $state) => $state ? 'success' : 'danger'),
                    ]),
                
                // Se removió la sección de vista previa del PDF
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Editar'),
            Actions\Action::make('view')
                ->label('Ver documento')
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->url(fn() => asset('storage/' . $this->record->path))
                ->openUrlInNewTab()
                ->visible(fn() => $this->record->path && Storage::disk('public')->exists($this->record->path)),
            Actions\Action::make('download')
                ->label('Descargar documento')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $filePath = storage_path('app/public/' . $this->record->path);
                    
                    if (file_exists($filePath)) {
                        $filename = 'Documento_' . $this->record->registration_number . '_' . $this->record->name . '.pdf';
                        return response()->download($filePath, $filename, [
                            'Content-Type' => 'application/pdf',
                        ]);
                    }
                    
                    // Si el archivo no existe, mostrar notificación de error
                    Notification::make()
                        ->title('Error de descarga')
                        ->body('No se encontró el archivo para descargar.')
                        ->danger()
                        ->send();
                })
                ->visible(fn() => $this->record->path && Storage::disk('public')->exists($this->record->path)),
            Actions\Action::make('derivar')
                ->label('Derivar documento')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Derivar documento')
                ->modalDescription('¿Está seguro que desea derivar este documento?')
                ->modalSubmitActionLabel('Sí, derivar')
                ->modalCancelActionLabel('No, cancelar')
                ->visible(fn() => !$this->record->is_derived)
                ->action(function () {
                    $this->record->update(['is_derived' => true]);
                }),
        ];
    }
}