<?php

namespace App\Filament\Resources\DerivationResource\Pages;

use App\Filament\Resources\DerivationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use App\Models\DerivationDetail;
use Illuminate\Support\Facades\Auth;

class EditDerivation extends EditRecord
{
    protected static string $resource = DerivationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->modalHeading('Eliminar derivación')
                ->modalDescription('¿Está seguro que desea eliminar esta derivación? Esta acción no se puede deshacer.')
                ->modalSubmitActionLabel('Sí, eliminar')
                ->modalCancelActionLabel('No, cancelar'),
            Actions\ViewAction::make(),
        ];
    }
      protected function afterSave(): void
    {
        // Guardar el comentario si se proporcionó
        if ($this->data['comments'] ?? false) {
            \App\Models\DerivationDetail::create([
                'derivation_id' => $this->record->id,
                'comments' => $this->data['comments'],
                'user_id' => Auth::id(),
                'status' => $this->data['status'] ?? $this->record->status
            ]);
        }
        
        Notification::make()
            ->title('Derivación actualizada')
            ->success()
            ->send();
    }
    
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // No cargar comentarios existentes en el formulario de edición
        // para evitar sobrescribir comentarios anteriores
        $data['comments'] = '';
        
        return $data;
    }
}