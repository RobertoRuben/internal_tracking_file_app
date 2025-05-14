<?php

namespace App\Filament\Resources\ReceivedDocumentResource\Pages;

use App\Filament\Resources\ReceivedDocumentResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;

class ViewReceivedDocument extends ViewRecord
{
    protected static string $resource = ReceivedDocumentResource::class;

    protected function getHeaderActions(): array
    {        $userDepartmentId = Auth::user()->employee->department_id ?? null;
        $record = $this->getRecord();
        $hasPendingDerivation = false;
        
        if ($userDepartmentId) {
            // Verificar derivaciones dirigidas a este departamento que no tengan detalle de 'Recibido' o 'Rechazado'
            $derivation = $record->derivations()
                ->where('destination_department_id', $userDepartmentId)
                ->latest()
                ->first();
                
            if ($derivation) {
                // Verificar si existe algún detalle con estado Recibido o Rechazado
                $hasReceivedOrRejected = \App\Models\DerivationDetail::where('derivation_id', $derivation->id)
                    ->whereIn('status', ['Recibido', 'Rechazado'])
                    ->exists();
                    
                $hasPendingDerivation = !$hasReceivedOrRejected;
            }
        }
        
        $actions = [];
        
        if ($record->path && Storage::disk('public')->exists($record->path)) {
            $actions[] = Actions\Action::make('viewDocument')
                ->label('Ver PDF')
                ->icon('heroicon-o-document-text')
                ->color('success')
                ->url(fn () => asset('storage/' . $record->path))
                ->openUrlInNewTab();
        }
        
        if ($hasPendingDerivation) {
            $actions[] = Actions\Action::make('receiveDocument')
                ->label('Recibir')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->form([
                    \Filament\Forms\Components\Textarea::make('comments')
                        ->label('Observaciones')
                        ->placeholder('Ingrese alguna observación sobre la recepción del documento...')
                ])                ->action(function (array $data): void {
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
                    $derivation = $this->getRecord()->derivations()
                        ->where('destination_department_id', $userDepartmentId)
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
                        
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->getRecord()]));
                });
                
            $actions[] = Actions\Action::make('rejectDocument')
                ->label('Rechazar')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->form([
                    \Filament\Forms\Components\Textarea::make('comments')
                        ->label('Motivo del rechazo')
                        ->placeholder('Explique el motivo por el que rechaza este documento...')
                        ->required()
                        ->validationMessages([
                            'required' => 'Debe proporcionar un motivo para rechazar el documento.'
                        ]),
                ])                ->action(function (array $data): void {
                    $userDepartmentId = Auth::user()->employee->department_id ?? null;
                    
                    if (!$userDepartmentId) {
                        Notification::make()
                            ->danger()
                            ->title('Error')
                            ->body('No se pudo determinar su departamento.')
                            ->send();
                        return;
                    }
                    
                    $derivation = $this->getRecord()->derivations()
                        ->where('destination_department_id', $userDepartmentId)
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
                        
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->getRecord()]));
                });
        }
        
        return $actions;
    }
}
