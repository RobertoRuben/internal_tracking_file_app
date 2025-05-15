<?php

namespace App\Filament\Widgets;

use App\Models\Derivation;
use App\Models\DerivationDetail;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AverageResponseTimeLineChart extends ChartWidget
{
    protected static ?string $heading = 'Tiempo Promedio de Respuesta';
    protected static ?string $pollingInterval = '60s';
    protected static ?int $sort = 4;

    public function getColumnSpan(): int|string|array
    {
        return 1;
    }

    protected function getData(): array
    {
        $userDepartmentId = Auth::user()->employee->department_id ?? null;

        if (!$userDepartmentId) {
            return [
                'datasets' => [
                    [
                        'label' => 'Sin departamento asignado',
                        'data' => [0, 0, 0, 0, 0, 0],
                        'borderColor' => '#e5e7eb',
                    ],
                ],
                'labels' => ['Sin datos'],
            ];
        }

        $labels = [];
        $responseTimesData = [];

        // Obtener los últimos 6 meses
        for ($i = 5; $i >= 0; $i--) {
            $startDate = Carbon::now()->subMonths($i)->startOfMonth();
            $endDate = Carbon::now()->subMonths($i)->endOfMonth();
            
            $month = $startDate->format('M Y');
            $labels[] = $month;
            
            // Buscar derivaciones al departamento durante ese mes
            $derivations = Derivation::where('destination_department_id', $userDepartmentId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();
            
            $responseTimesInMonth = [];
            
            foreach ($derivations as $derivation) {
                // Buscar el primer detalle de respuesta (Recibido o Rechazado)
                $responseDetail = DerivationDetail::where('derivation_id', $derivation->id)
                    ->whereIn('status', ['Recibido', 'Rechazado'])
                    ->orderBy('created_at', 'asc')
                    ->first();
                
                if ($responseDetail) {
                    // Calcular días de respuesta
                    $creationDate = $derivation->created_at;
                    $responseDate = $responseDetail->created_at;
                    $diffInDays = $creationDate->diffInHours($responseDate) / 24;
                    
                    $responseTimesInMonth[] = $diffInDays;
                }
            }
            
            // Calcular el promedio de tiempos de respuesta para este mes
            $avgResponseTime = count($responseTimesInMonth) > 0 
                ? array_sum($responseTimesInMonth) / count($responseTimesInMonth) 
                : null;
            
            $responseTimesData[] = $avgResponseTime;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Días promedio de respuesta',
                    'data' => $responseTimesData,
                    'fill' => false,
                    'borderColor' => '#6366f1', // Índigo
                    'tension' => 0.1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
