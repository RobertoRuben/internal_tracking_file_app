<?php

namespace App\Filament\Resources\ChargeBookResource\Pages;

use App\Filament\Resources\ChargeBookResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ManageChargeBooks extends ManageRecords
{
    protected static string $resource = ChargeBookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Registrar cargo')
                ->icon('heroicon-o-plus-circle')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Documento registrado')
                        ->body('El documento ha sido registrado correctamente en el cuaderno de cargos.')
                ),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            //
        ];
    }
    
    // Verificar que el usuario tenga un departamento asignado
    public function mount(): void
    {
        $userDepartmentId = Auth::user()->employee->department_id ?? null;
        
        if (!$userDepartmentId) {
            // Redirigir o mostrar mensaje
            Notification::make()
                ->danger()
                ->title('Error de acceso')
                ->body('Debe tener un departamento asignado para acceder al cuaderno de cargos.')
                ->persistent()
                ->send();
            
            // Opcionalmente, redirigir a otra página
            // return redirect()->route('filament.admin.pages.dashboard');
        }
        
        parent::mount();
    }
}
