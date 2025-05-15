<?php

namespace App\Filament\Widgets;

use App\Models\Derivation;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DocumentsStatusPieChart extends ChartWidget
{
    protected static ?string $heading = 'Estado de Documentos Recibidos';
    protected static ?string $pollingInterval = '60s';
    protected static ?int $sort = 3;
    protected int $height = 200;

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
                        'data' => [0],
                        'backgroundColor' => ['#e5e7eb'],
                    ],
                ],
                'labels' => ['Sin departamento asignado'],
            ];
        }

        // Total de documentos recibidos
        $totalReceived = Derivation::where('destination_department_id', $userDepartmentId)->count();

        // Documentos pendientes (sin detalles de recepción o rechazo)
        $pendingCount = Derivation::where('destination_department_id', $userDepartmentId)
            ->whereDoesntHave('details', function ($query) {
                $query->whereIn('status', ['Recibido', 'Rechazado']);
            })
            ->count();

        // Documentos recibidos
        $receivedCount = Derivation::where('destination_department_id', $userDepartmentId)
            ->whereHas('details', function ($query) {
                $query->where('status', 'Recibido');
            })
            ->count();

        // Documentos rechazados
        $rejectedCount = Derivation::where('destination_department_id', $userDepartmentId)
            ->whereHas('details', function ($query) {
                $query->where('status', 'Rechazado');
            })
            ->count();

        // Si no hay documentos recibidos, mostrar un mensaje adecuado
        if ($totalReceived === 0) {
            return [
                'datasets' => [
                    [
                        'data' => [1],
                        'backgroundColor' => ['#e5e7eb'],
                    ],
                ],
                'labels' => ['Sin documentos recibidos'],
            ];
        }

        return [
            'datasets' => [
                [
                    'data' => [$pendingCount, $receivedCount, $rejectedCount],
                    'backgroundColor' => [
                        '#f59e0b', // Amarillo - Pendientes
                        '#10b981', // Verde - Recibidos
                        '#ef4444', // Rojo - Rechazados
                    ],
                ],
            ],
            'labels' => ['Pendientes', 'Recibidos', 'Rechazados'],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'font' => [
                            'size' => 11,
                        ],
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
            'responsive' => true,
            'cutout' => '0%',
            'layout' => [
                'padding' => [
                    'top' => 5,
                    'bottom' => 5,
                    'left' => 5,
                    'right' => 5,
                ],
            ],
        ];
    }
}
