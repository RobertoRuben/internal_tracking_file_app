<?php

namespace App\Filament\Resources\ReceivedDocumentResource\Pages;

use App\Filament\Resources\ReceivedDocumentResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListReceivedDocuments extends ListRecords
{
    protected static string $resource = ReceivedDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No hay acciones de creación ya que este recurso es solo para ver documentos recibidos
        ];
    }
}
