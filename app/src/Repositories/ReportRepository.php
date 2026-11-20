<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ReportRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return array{rows: list<array<string,mixed>>, total_docs: int, total_monto: float}
     */
    public function comprasByDate(string $fechaMin, string $fechaMax): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT cod_fac, fecha_fac, cod_pro, nom_pro, total_fac
             FROM compra
             WHERE fecha_fac >= :min AND fecha_fac <= :max
             ORDER BY fecha_fac ASC, cod_fac ASC'
        );
        $stmt->execute(['min' => $fechaMin, 'max' => $fechaMax]);
        $rows = $stmt->fetchAll();

        $sum = $this->pdo->prepare(
            'SELECT COUNT(*) AS total_docs, COALESCE(SUM(total_fac), 0) AS total_monto
             FROM compra
             WHERE fecha_fac >= :min AND fecha_fac <= :max'
        );
        $sum->execute(['min' => $fechaMin, 'max' => $fechaMax]);
        $agg = $sum->fetch() ?: ['total_docs' => 0, 'total_monto' => 0];

        return [
            'rows' => $rows,
            'total_docs' => (int) $agg['total_docs'],
            'total_monto' => (float) $agg['total_monto'],
        ];
    }

    /**
     * @return array{rows: list<array<string,mixed>>, total_docs: int, total_monto: float}
     */
    public function ventasByDate(string $fechaMin, string $fechaMax): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT cod_fac, fecha_fac, cod_cli, nom_cli, total_fac, tot_bul
             FROM venta
             WHERE fecha_fac >= :min AND fecha_fac <= :max
             ORDER BY fecha_fac ASC, cod_fac ASC'
        );
        $stmt->execute(['min' => $fechaMin, 'max' => $fechaMax]);
        $rows = $stmt->fetchAll();

        $sum = $this->pdo->prepare(
            'SELECT COUNT(*) AS total_docs, COALESCE(SUM(total_fac), 0) AS total_monto
             FROM venta
             WHERE fecha_fac >= :min AND fecha_fac <= :max'
        );
        $sum->execute(['min' => $fechaMin, 'max' => $fechaMax]);
        $agg = $sum->fetch() ?: ['total_docs' => 0, 'total_monto' => 0];

        return [
            'rows' => $rows,
            'total_docs' => (int) $agg['total_docs'],
            'total_monto' => (float) $agg['total_monto'],
        ];
    }
}
