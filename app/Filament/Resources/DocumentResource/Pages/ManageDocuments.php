<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Auth;

class ManageDocuments extends ManageRecords
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Crear documento')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Documento registrado')
                        ->body('El documento ha sido registrado automáticamente a su nombre y departamento.')
                        ->duration(5000)
                ),
        ];
    }

    protected function getTableRecordsPerPageSelectOptions(): array 
    {
        return [10, 25, 50, 100];
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();
        
        // Obtener el ID del departamento del usuario actual
        $userDepartmentId = Auth::user()->employee->department_id ?? null;
        
        if ($userDepartmentId) {
            // Filtrar documentos por departamento del usuario (propios, recibidos o derivados)
            $query->where(function (Builder $subQuery) use ($userDepartmentId) {
                // Documentos creados por el departamento del usuario
                $subQuery->where('created_by_department_id', $userDepartmentId)
                    // Documentos derivados al departamento del usuario
                    ->orWhereHas('derivations', function (Builder $derivationQuery) use ($userDepartmentId) {
                        $derivationQuery->where('destination_department_id', $userDepartmentId);
                    })
                    // Documentos recibidos por el departamento del usuario
                    ->orWhereHas('derivations', function (Builder $derivationQuery) use ($userDepartmentId) {
                        $derivationQuery->where('origin_department_id', $userDepartmentId);
                    });
            });
        }
        
        // Búsqueda global que no está vinculada a un solo campo
        $search = request('tableSearch');
        
        if ($search) {
            $query->where(function (Builder $query) use ($search) {
                $query->where('doc_code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('registration_number', 'like', "%{$search}%")
                    ->orWhereHas('employee', function (Builder $query) use ($search) {
                        $query->where('names', 'like', "%{$search}%")
                              ->orWhere('paternal_surname', 'like', "%{$search}%")
                              ->orWhere('maternal_surname', 'like', "%{$search}%");
                    })
                    ->orWhereHas('creatorDepartment', function (Builder $query) use ($search) {
                        $query->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('registeredBy', function (Builder $query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                              ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }
        
        return $query;
    }
}