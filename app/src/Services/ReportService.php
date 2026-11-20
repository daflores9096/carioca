<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ReportRepository;
use App\Support\HttpException;

final class ReportService
{
    public function __construct(private readonly ReportRepository $reports)
    {
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function compras(array $params): array
    {
        [$min, $max] = $this->range($params);
        $data = $this->reports->comprasByDate($min, $max);

        return [
            'tipo' => 'compras',
            'fecha_min' => $min,
            'fecha_max' => $max,
            'data' => $data['rows'],
            'total_docs' => $data['total_docs'],
            'total_monto' => round($data['total_monto'], 2),
        ];
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function ventas(array $params): array
    {
        [$min, $max] = $this->range($params);
        $data = $this->reports->ventasByDate($min, $max);

        return [
            'tipo' => 'ventas',
            'fecha_min' => $min,
            'fecha_max' => $max,
            'data' => $data['rows'],
            'total_docs' => $data['total_docs'],
            'total_monto' => round($data['total_monto'], 2),
        ];
    }

    /**
     * @param array<string, mixed> $params
     * @return array{0:string,1:string}
     */
    private function range(array $params): array
    {
        $min = trim((string) ($params['fecha_min'] ?? ''));
        $max = trim((string) ($params['fecha_max'] ?? ''));

        if ($min === '') {
            $min = date('Y-m-01');
        }
        if ($max === '') {
            $max = date('Y-m-d');
        }

        if (!$this->validDate($min) || !$this->validDate($max)) {
            throw new HttpException('Fechas inválidas. Use formato YYYY-MM-DD.', 422);
        }
        if ($min > $max) {
            throw new HttpException('La fecha inicial no puede ser mayor que la final.', 422);
        }

        return [$min, $max];
    }

    private function validDate(string $d): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
            return false;
        }
        [$y, $m, $day] = array_map('intval', explode('-', $d));
        return checkdate($m, $day, $y);
    }
}
