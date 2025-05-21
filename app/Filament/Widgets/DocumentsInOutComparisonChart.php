<?php

namespace App\Filament\Widgets;

use App\Models\Derivation;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DocumentsInOutComparisonChart extends ChartWidget
{
    protected static ?string $heading = 'Comparativa Documentos Enviados vs Recibidos';
    protected static ?string $pollingInterval = '60s';
    protected static ?int $sort = 2;

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
                    ],
                ],
                'labels' => ['Sin datos'],
            ];
        }

        // Documentos enviados por mes (últimos 6 meses)
        $sentData = Derivation::where('origin_department_id', $userDepartmentId)
            ->where('created_at', '>=', Carbon::now()->subMonths(6))
            ->select(
                DB::raw('EXTRACT(MONTH FROM created_at) as month'),
                DB::raw('EXTRACT(YEAR FROM created_at) as year'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        // Documentos recibidos por mes (últimos 6 meses)
        $receivedData = Derivation::where('destination_department_id', $userDepartmentId)
            ->where('created_at', '>=', Carbon::now()->subMonths(6))
            ->select(
                DB::raw('EXTRACT(MONTH FROM created_at) as month'),
                DB::raw('EXTRACT(YEAR FROM created_at) as year'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $labels = [];
        $sentCounts = [];
        $receivedCounts = [];

        // Crear un array de los últimos 6 meses
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthKey = $month->format('Y-m');
            $labels[] = $month->format('M Y');
            $sentCounts[$monthKey] = 0;
            $receivedCounts[$monthKey] = 0;
        }

        // Rellenar con datos de documentos enviados
        foreach ($sentData as $record) {
            $date = Carbon::createFromDate((int)$record->year, (int)$record->month, 1);
            $monthKey = $date->format('Y-m');
            
            if (isset($sentCounts[$monthKey])) {
                $sentCounts[$monthKey] = $record->count;
            }
        }

        // Rellenar con datos de documentos recibidos
        foreach ($receivedData as $record) {
            $date = Carbon::createFromDate((int)$record->year, (int)$record->month, 1);
            $monthKey = $date->format('Y-m');
            
            if (isset($receivedCounts[$monthKey])) {
                $receivedCounts[$monthKey] = $record->count;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Documentos Enviados',
                    'data' => array_values($sentCounts),
                    'backgroundColor' => '#10b981', // Verde
                ],
                [
                    'label' => 'Documentos Recibidos',
                    'data' => array_values($receivedCounts),
                    'backgroundColor' => '#3b82f6', // Azul
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
