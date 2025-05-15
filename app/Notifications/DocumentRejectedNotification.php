<?php

namespace App\Notifications;

use App\Models\Derivation;
use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class DocumentRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Derivation $derivation,
        protected Document $document
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return FilamentNotification::make()
            ->title('Documento rechazado')
            ->icon('heroicon-o-x-circle')
            ->iconColor('danger')->body("El documento {$this->document->name} ha sido rechazado por {$this->derivation->derivatedBy->name}")
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->label('Ver documento')
                    ->url(route('filament.app.resources.documents.view', $this->document))
                    ->button()
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
