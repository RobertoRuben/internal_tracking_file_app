<?php

namespace App\Filament\Widgets;

use App\Models\Document;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DocumentsCreatedChart extends ChartWidget
{
    protected static ?string $heading = 'Documentos Creados por Mes';
    protected static ?string $pollingInterval = '60s';
    protected static ?int $sort = 1;

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
                        'backgroundColor' => ['#3b82f6'],
                    ],
                ],
                'labels' => ['Sin datos'],
            ];
        }

        $data = Document::where('created_by_department_id', $userDepartmentId)
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

        $chartData = [];
        $labels = [];

        // Crear un array de los últimos 6 meses
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthKey = $month->format('Y-m');
            $labels[] = $month->format('M Y');
            $chartData[$monthKey] = 0;
        }

        // Rellenar con datos reales
        foreach ($data as $record) {
            $date = Carbon::createFromDate((int)$record->year, (int)$record->month, 1);
            $monthKey = $date->format('Y-m');
            
            if (isset($chartData[$monthKey])) {
                $chartData[$monthKey] = $record->count;
            }
        }

        $colors = [
            '#3b82f6', // Azul primario
            '#2563eb',
            '#1d4ed8',
            '#1e40af',
            '#1e3a8a',
            '#172554',
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Documentos Creados',
                    'data' => array_values($chartData),
                    'backgroundColor' => $colors,
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
