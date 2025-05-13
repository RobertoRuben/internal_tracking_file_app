<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ManageEmployees extends ManageRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Crear empleado'),
        ];
    }

    protected function getTableRecordsPerPageSelectOptions(): array 
    {
        return [10, 25, 50, 100];
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();
        
        // Búsqueda global que no está vinculada a un solo campo
        $search = request('tableSearch');
        
        if ($search) {
            $query->where(function (Builder $query) use ($search) {
                $query->where('dni', 'like', "%{$search}%")
                    ->orWhere('names', 'like', "%{$search}%")
                    ->orWhere('paternal_surname', 'like', "%{$search}%")
                    ->orWhere('maternal_surname', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhereHas('department', function (Builder $query) use ($search) {
                        $query->where('name', 'like', "%{$search}%");
                    });
            });
        }
        
        return $query;
    }
}
