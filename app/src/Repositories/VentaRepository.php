<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\ListQuery;
use PDO;

final class VentaRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function paginate(ListQuery $query): array
    {
        $where = '';
        $params = [];
        if ($query->q !== '') {
            $where = 'WHERE CAST(cod_fac AS CHAR) LIKE :q1 OR nom_cli LIKE :q2';
            $like = '%' . $query->q . '%';
            $params = ['q1' => $like, 'q2' => $like];
        }

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM venta $where");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT cod_fac, fecha_fac, cod_cli, nom_cli, dire_cli, traspaso, total_fac, tot_bul
                FROM venta
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
            'SELECT cod_fac, fecha_fac, cod_cli, nom_cli, dire_cli, traspaso, total_fac, tot_bul
             FROM venta WHERE cod_fac = :cod LIMIT 1'
        );
        $stmt->execute(['cod' => $codFac]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function findLines(int $codFac): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.Id AS id, a.cod_fac, a.cod_item, a.bultos, a.cant_fac, a.precio_uni, a.importe_fac,
                    i.nom_item, i.unid_item, i.caja_item, i.precio_item
             FROM venta_aux a
             LEFT JOIN item i ON i.cod_item = a.cod_item
             WHERE a.cod_fac = :cod
             ORDER BY a.Id'
        );
        $stmt->execute(['cod' => $codFac]);
        return $stmt->fetchAll();
    }

    public function exists(int $codFac): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM venta WHERE cod_fac = :cod LIMIT 1');
        $stmt->execute(['cod' => $codFac]);
        return (bool) $stmt->fetchColumn();
    }

    public function nextCode(): int
    {
        // Ventas: correlativo simple (sin filtro >10000; eso solo aplica a compras).
        $stmt = $this->pdo->query(
            "SELECT COALESCE(MAX(cod_fac), 0)
             FROM venta
             WHERE nom_cli <> 'INVENTARIO INICIAL'"
        );
        $max = (int) $stmt->fetchColumn();
        return $max + 1;
    }

    public function insertHeader(array $data): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO venta (cod_fac, fecha_fac, cod_cli, nom_cli, dire_cli, traspaso, total_fac, tot_bul)
             VALUES (:cod_fac, :fecha_fac, :cod_cli, :nom_cli, :dire_cli, :traspaso, :total_fac, :tot_bul)'
        );
        $stmt->execute($data);
    }

    public function insertLine(array $data): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO venta_aux (cod_fac, cod_item, bultos, cant_fac, precio_uni, importe_fac)
             VALUES (:cod_fac, :cod_item, :bultos, :cant_fac, :precio_uni, :importe_fac)'
        );
        $stmt->execute($data);
    }

    public function deleteHeader(int $codFac): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM venta WHERE cod_fac = :cod');
        $stmt->execute(['cod' => $codFac]);
    }

    public function deleteLines(int $codFac): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM venta_aux WHERE cod_fac = :cod');
        $stmt->execute(['cod' => $codFac]);
    }
}
