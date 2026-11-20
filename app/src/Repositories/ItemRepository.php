<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\ListQuery;
use PDO;

final class ItemRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return array{data:list<array>, total:int, page:int, per_page:int}
     */
    public function paginate(ListQuery $query): array
    {
        $where = '';
        $params = [];
        if ($query->q !== '') {
            $where = 'WHERE cod_item LIKE :q1 OR nom_item LIKE :q2 OR deta_item LIKE :q3';
            $like = '%' . $query->q . '%';
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
        }

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM item $where");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT cod_item, nom_item, unid_item, precio_item, caja_item, exi_max, existencia, exi_min, deta_item
                FROM item
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

    public function find(string $codItem): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT cod_item, nom_item, unid_item, precio_item, caja_item, exi_max, existencia, exi_min, deta_item
             FROM item WHERE cod_item = :cod LIMIT 1'
        );
        $stmt->execute(['cod' => $codItem]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function exists(string $codItem): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM item WHERE cod_item = :cod LIMIT 1');
        $stmt->execute(['cod' => $codItem]);
        return (bool) $stmt->fetchColumn();
    }

    public function insert(array $data): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO item (cod_item, nom_item, unid_item, precio_item, caja_item, exi_max, existencia, exi_min, deta_item)
             VALUES (:cod_item, :nom_item, :unid_item, :precio_item, :caja_item, :exi_max, :existencia, :exi_min, :deta_item)'
        );
        $stmt->execute($data);
    }

    public function update(string $originalCod, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE item SET
                cod_item = :cod_item,
                nom_item = :nom_item,
                unid_item = :unid_item,
                precio_item = :precio_item,
                caja_item = :caja_item,
                exi_max = :exi_max,
                existencia = :existencia,
                exi_min = :exi_min,
                deta_item = :deta_item
             WHERE cod_item = :original_cod'
        );
        $data['original_cod'] = $originalCod;
        $stmt->execute($data);
    }

    public function delete(string $codItem): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM item WHERE cod_item = :cod');
        $stmt->execute(['cod' => $codItem]);
    }
}
