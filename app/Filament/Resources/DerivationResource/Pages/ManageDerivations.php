<?php

namespace App\Filament\Resources\DerivationResource\Pages;

use App\Filament\Resources\DerivationResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ManageDerivations extends ManageRecords
{
    protected static string $resource = DerivationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Crear derivación')
                ->icon('heroicon-o-plus-circle')
                ->using(function (array $data) {
                    $derivation = static::getResource()::getModel()::create($data);
                    
                    $user = auth()->user();
                    $destinationDepartment = \App\Models\Department::find($data['destination_department_id']);
                    
                    $systemMessage = "Documento enviado por {$user->name} al departamento {$destinationDepartment->name}.";
                    
                    \App\Models\DerivationDetail::create([
                        'derivation_id' => $derivation->id,
                        'comments' => $systemMessage . (isset($data['comments']) && !empty($data['comments']) ? "\n\nObservaciones: {$data['comments']}" : ""),
                        'user_id' => auth()->id(),
                        'status' => 'Enviado'
                    ]);
                    
                    return $derivation;
                })
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Derivación creada')
                        ->body('La derivación se ha creado correctamente y el documento ha sido marcado como derivado.')
                ),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();
        
        $userDepartmentId = Auth::user()->employee->department_id ?? null;
        
        if ($userDepartmentId) {
            $query->where(function (Builder $subQuery) use ($userDepartmentId) {
                $subQuery->where('origin_department_id', $userDepartmentId)
                    ->orWhere('destination_department_id', $userDepartmentId);
            });
        }
        
        return $query;
    }
}