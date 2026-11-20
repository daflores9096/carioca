<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\ListQuery;
use PDO;

final class CompraRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function paginate(ListQuery $query): array
    {
        $where = '';
        $params = [];
        if ($query->q !== '') {
            $where = 'WHERE CAST(cod_fac AS CHAR) LIKE :q1 OR nom_pro LIKE :q2';
            $like = '%' . $query->q . '%';
            $params = ['q1' => $like, 'q2' => $like];
        }

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM compra $where");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT cod_fac, fecha_fac, cod_pro, nom_pro, total_fac
                FROM compra
                $where
                ORDER BY {$query->sort} {$query->dir}
                LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
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

    public function findHeader(int $codFac): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT cod_fac, fecha_fac, cod_pro, nom_pro, total_fac
             FROM compra WHERE cod_fac = :cod LIMIT 1'
        );
        $stmt->execute(['cod' => $codFac]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function findLines(int $codFac): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.Id AS id, a.cod_fac, a.cod_item, a.cant_fac, a.precio_uni, a.precio_ven, a.importe_fac,
                    i.nom_item, i.unid_item, i.caja_item
             FROM compra_aux a
             LEFT JOIN item i ON i.cod_item = a.cod_item
             WHERE a.cod_fac = :cod
             ORDER BY a.Id'
        );
        $stmt->execute(['cod' => $codFac]);
        return $stmt->fetchAll();
    }

    public function exists(int $codFac): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM compra WHERE cod_fac = :cod LIMIT 1');
        $stmt->execute(['cod' => $codFac]);
        return (bool) $stmt->fetchColumn();
    }

    public function nextCode(): int
    {
        // Excluye series especiales (>10000) e inventario inicial del correlativo operativo.
        $stmt = $this->pdo->query(
            "SELECT COALESCE(MAX(cod_fac), 0)
             FROM compra
             WHERE cod_fac <= 10000
               AND nom_pro <> 'INVENTARIO INICIAL'"
        );
        $max = (int) $stmt->fetchColumn();
        return $max + 1;
    }

    public function insertHeader(array $data): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO compra (cod_fac, fecha_fac, cod_pro, nom_pro, total_fac)
             VALUES (:cod_fac, :fecha_fac, :cod_pro, :nom_pro, :total_fac)'
        );
        $stmt->execute($data);
    }

    public function insertLine(array $data): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO compra_aux (cod_fac, cod_item, cant_fac, precio_uni, precio_ven, importe_fac)
             VALUES (:cod_fac, :cod_item, :cant_fac, :precio_uni, :precio_ven, :importe_fac)'
        );
        $stmt->execute($data);
    }

    public function deleteHeader(int $codFac): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM compra WHERE cod_fac = :cod');
        $stmt->execute(['cod' => $codFac]);
    }

    public function deleteLines(int $codFac): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM compra_aux WHERE cod_fac = :cod');
        $stmt->execute(['cod' => $codFac]);
    }
}
