<?php

namespace App\Filament\Widgets;

use App\Models\Document;
use App\Models\Derivation;
use App\Models\DerivationDetail;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DocumentsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getHeading(): ?string
    {
        return 'Estadísticas de Documentos';
    }

    protected function getDescription(): ?string
    {
        $departmentName = Auth::user()->employee->department->name ?? 'No asignado';
        return "Estadísticas de documentos del departamento: {$departmentName}";
    }

    protected function getStats(): array
    {
        $userDepartmentId = Auth::user()->employee->department_id ?? null;

        if (!$userDepartmentId) {
            return [
                Stat::make('Sin departamento asignado', 'No disponible')
                    ->description('Necesita tener un departamento asignado para ver estadísticas')
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color('danger'),
            ];
        }

        $createdDocuments = Document::where('created_by_department_id', $userDepartmentId)->count();
        $recentCreatedDocuments = Document::where('created_by_department_id', $userDepartmentId)
            ->whereDate('created_at', '>=', now()->subDays(7))
            ->count();

        $derivedDocuments = Derivation::where('origin_department_id', $userDepartmentId)->count();
        $recentDerivedDocuments = Derivation::where('origin_department_id', $userDepartmentId)
            ->whereDate('created_at', '>=', now()->subDays(7))
            ->count();

        $receivedDocuments = Derivation::where('destination_department_id', $userDepartmentId)->count();
        $recentReceivedDocuments = Derivation::where('destination_department_id', $userDepartmentId)
            ->whereDate('created_at', '>=', now()->subDays(7))
            ->count();

        $pendingDocuments = Derivation::where('destination_department_id', $userDepartmentId)
            ->whereDoesntHave('details', function ($query) {
                $query->whereIn('status', ['Recibido', 'Rechazado']);
            })
            ->count();

        $rejectedDocuments = Derivation::where('destination_department_id', $userDepartmentId)
            ->whereHas('details', function ($query) {
                $query->where('status', 'Rechazado');
            })
            ->count();

        $recentRejectedDocuments = Derivation::where('destination_department_id', $userDepartmentId)
            ->whereHas('details', function ($query) {
                $query->where('status', 'Rechazado')
                    ->whereDate('created_at', '>=', now()->subDays(7));
            })
            ->count();

        $averageResponseTime = 0;
        $respondedCount = 0;
        $responseWithin24h = 0;

        $derivations = Derivation::where('destination_department_id', $userDepartmentId)
            ->whereHas('details', function ($query) {
                $query->whereIn('status', ['Recibido', 'Rechazado']);
            })
            ->get();

        foreach ($derivations as $derivation) {
            $creationDate = $derivation->created_at;

            $responseDetail = DerivationDetail::where('derivation_id', $derivation->id)
                ->whereIn('status', ['Recibido', 'Rechazado'])
                ->orderBy('created_at', 'asc')
                ->first();

            if ($responseDetail) {
                $responseDate = $responseDetail->created_at;

                $diffInDays = $creationDate->diffInHours($responseDate) / 24;
                $averageResponseTime += $diffInDays;
                $respondedCount++;

                if ($creationDate->diffInHours($responseDate) <= 24) {
                    $responseWithin24h++;
                }
            }
        }

        if ($respondedCount > 0) {
            $averageResponseTime = round($averageResponseTime / $respondedCount, 1);
            $responseWithin24hPercent = round(($responseWithin24h / $respondedCount) * 100);
        } else {
            $averageResponseTime = 0;
            $responseWithin24hPercent = 0;
        }

        $createdStats = $this->getHistoricalData('created_by_department_id', $userDepartmentId);
        $derivedStats = $this->getHistoricalDerivations('origin_department_id', $userDepartmentId);
        $receivedStats = $this->getHistoricalDerivations('destination_department_id', $userDepartmentId);
        $rejectedStats = $this->getHistoricalRejections($userDepartmentId);

        return [
            Stat::make('Documentos Creados', $createdDocuments)
                ->description($recentCreatedDocuments . ' en los últimos 7 días')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary')
                ->chart($createdStats),

            Stat::make('Documentos Enviados', $derivedDocuments)
                ->description($recentDerivedDocuments . ' en los últimos 7 días')
                ->descriptionIcon('heroicon-m-paper-airplane')
                ->color('success')
                ->chart($derivedStats),

            Stat::make('Documentos Recibidos', $receivedDocuments)
                ->description($recentReceivedDocuments . ' en los últimos 7 días')
                ->descriptionIcon('heroicon-m-inbox-arrow-down')
                ->color('info')
                ->chart($receivedStats),

            Stat::make('Pendientes por Recibir', $pendingDocuments)
                ->description('Documentos sin confirmación de recepción')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingDocuments > 0 ? 'warning' : 'success'),

            Stat::make('Documentos Rechazados', $rejectedDocuments)
                ->description($recentRejectedDocuments . ' en los últimos 7 días')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->chart($rejectedStats),

            Stat::make('Tiempo Promedio de Respuesta', $averageResponseTime . ' días')
                ->description($responseWithin24hPercent . '% respondidos en 24h')
                ->descriptionIcon('heroicon-m-bolt')
                ->color($averageResponseTime <= 1 ? 'success' : ($averageResponseTime <= 3 ? 'warning' : 'danger')),
        ];
    }

    protected function getHistoricalData(string $field, int $departmentId): array
    {
        $stats = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $count = Document::where($field, $departmentId)
                ->whereDate('created_at', $date)
                ->count();

            $stats[] = $count;
        }

        return $stats;
    }

    protected function getHistoricalDerivations(string $field, int $departmentId): array
    {
        $stats = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $count = Derivation::where($field, $departmentId)
                ->whereDate('created_at', $date)
                ->count();

            $stats[] = $count;
        }

        return $stats;
    }

    protected function getHistoricalRejections(int $departmentId): array
    {
        $stats = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');

            $count = Derivation::where('destination_department_id', $departmentId)
                ->whereHas('details', function ($query) use ($date) {
                    $query->where('status', 'Rechazado')
                        ->whereDate('created_at', $date);
                })
                ->count();

            $stats[] = $count;
        }

        return $stats;
    }
}
