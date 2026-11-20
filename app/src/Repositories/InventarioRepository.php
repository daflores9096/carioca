<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\ListQuery;
use PDO;

final class InventarioRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function paginate(ListQuery $query): array
    {
        $where = '';
        $params = [];
        if ($query->q !== '') {
            $where = 'WHERE CAST(id_inv AS CHAR) LIKE :q1 OR descripcion LIKE :q2 OR estado LIKE :q3';
            $like = '%' . $query->q . '%';
            $params = ['q1' => $like, 'q2' => $like, 'q3' => $like];
        }

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM inventario $where");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT id_inv, fecha_lev, descripcion, fecha_ap, estado
                FROM inventario
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

    public function findHeader(int $idInv): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id_inv, fecha_lev, descripcion, fecha_ap, estado
             FROM inventario WHERE id_inv = :id LIMIT 1'
        );
        $stmt->execute(['id' => $idInv]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function findLines(int $idInv): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.id, a.id_inv, a.cod_item, a.existencia_inv, a.existencia_sis, a.diferencia,
                    i.nom_item, i.unid_item
             FROM inventario_aux a
             LEFT JOIN item i ON i.cod_item = a.cod_item
             WHERE a.id_inv = :id
             ORDER BY a.id'
        );
        $stmt->execute(['id' => $idInv]);
        return $stmt->fetchAll();
    }

    public function insertHeader(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO inventario (fecha_lev, descripcion, fecha_ap, estado)
             VALUES (:fecha_lev, :descripcion, :fecha_ap, :estado)'
        );
        $stmt->execute($data);
        return (int) $this->pdo->lastInsertId();
    }

    public function insertLine(array $data): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO inventario_aux (id_inv, cod_item, existencia_inv, existencia_sis, diferencia)
             VALUES (:id_inv, :cod_item, :existencia_inv, :existencia_sis, :diferencia)'
        );
        $stmt->execute($data);
    }

    public function updateHeader(int $idInv, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE inventario
             SET fecha_lev = :fecha_lev, descripcion = :descripcion
             WHERE id_inv = :id_inv'
        );
        $data['id_inv'] = $idInv;
        $stmt->execute($data);
    }

    public function deleteLines(int $idInv): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM inventario_aux WHERE id_inv = :id');
        $stmt->execute(['id' => $idInv]);
    }

    public function markApplied(int $idInv, string $fechaAp): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE inventario SET fecha_ap = :fecha_ap, estado = 'Aplicado' WHERE id_inv = :id"
        );
        $stmt->execute(['fecha_ap' => $fechaAp, 'id' => $idInv]);
    }

    public function deleteHeader(int $idInv): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM inventario WHERE id_inv = :id');
        $stmt->execute(['id' => $idInv]);
    }

    public function setItemExistencia(string $codItem, float $existencia): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE item SET existencia = :ex WHERE cod_item = :cod'
        );
        $stmt->execute(['ex' => $existencia, 'cod' => $codItem]);
    }
}
