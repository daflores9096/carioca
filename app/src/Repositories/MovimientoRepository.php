<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\ListQuery;
use PDO;

final class MovimientoRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return array{data:list<array>, total:int, page:int, per_page:int}
     */
    public function paginateByItem(string $codItem, ListQuery $query): array
    {
        $params = ['cod' => $codItem];

        $countStmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM movimiento WHERE cod_item = :cod'
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT id_mov, tipo_mov, cod_mov, cod_item, fecha_mov, cod_cli_pro, nom_cli_pro, entrada, salida
                FROM movimiento
                WHERE cod_item = :cod
                ORDER BY {$query->sort} {$query->dir}, id_mov {$query->dir}
                LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue('cod', $codItem);
        $stmt->bindValue('limit', $query->perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $query->offset(), PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $query->page,
            'per_page' => $query->perPage,
        ];
    }
}
