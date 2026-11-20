<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

final class StockService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function adjustExistencia(string $codItem, float $delta): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE item SET existencia = existencia + :delta WHERE cod_item = :cod'
        );
        $stmt->execute([
            'delta' => $delta,
            'cod' => $codItem,
        ]);
    }

    /**
     * @param array{
     *   tipo_mov:string,
     *   cod_mov:int,
     *   cod_item:string,
     *   fecha_mov:string,
     *   cod_cli_pro:int,
     *   nom_cli_pro:string,
     *   entrada:float,
     *   salida:float
     * } $row
     */
    public function insertMovimiento(array $row): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO movimiento (tipo_mov, cod_mov, cod_item, fecha_mov, cod_cli_pro, nom_cli_pro, entrada, salida)
             VALUES (:tipo_mov, :cod_mov, :cod_item, :fecha_mov, :cod_cli_pro, :nom_cli_pro, :entrada, :salida)'
        );
        $stmt->execute($row);
    }

    public function deleteMovimientos(int $codMov, string $tipoMov): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM movimiento WHERE cod_mov = :cod_mov AND tipo_mov = :tipo_mov'
        );
        $stmt->execute([
            'cod_mov' => $codMov,
            'tipo_mov' => $tipoMov,
        ]);
    }
}
