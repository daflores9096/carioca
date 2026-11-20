<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\ListQuery;
use PDO;

final class ProveedorRepository
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
            $where = 'WHERE nom_pro LIKE :q1 OR ciudad_pro LIKE :q2 OR email_pro LIKE :q3 OR tel_pro LIKE :q4 OR CAST(cod_pro AS CHAR) LIKE :q5';
            $like = '%' . $query->q . '%';
            $params = [
                'q1' => $like,
                'q2' => $like,
                'q3' => $like,
                'q4' => $like,
                'q5' => $like,
            ];
        }

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM proveedor $where");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT cod_pro, nom_pro, contacto_sec, dire_pro, ciudad_pro, tel_pro, tel_sec, email_pro, desc_pro
                FROM proveedor
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

    public function find(int $codPro): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT cod_pro, nom_pro, contacto_sec, dire_pro, ciudad_pro, tel_pro, tel_sec, email_pro, desc_pro
             FROM proveedor WHERE cod_pro = :cod LIMIT 1'
        );
        $stmt->execute(['cod' => $codPro]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function nextCode(): int
    {
        $max = (int) $this->pdo->query('SELECT COALESCE(MAX(cod_pro), 0) FROM proveedor')->fetchColumn();
        return $max + 1;
    }

    public function insert(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO proveedor (nom_pro, contacto_sec, dire_pro, ciudad_pro, tel_pro, tel_sec, email_pro, desc_pro)
             VALUES (:nom_pro, :contacto_sec, :dire_pro, :ciudad_pro, :tel_pro, :tel_sec, :email_pro, :desc_pro)'
        );
        $stmt->execute($data);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $codPro, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE proveedor SET
                nom_pro = :nom_pro,
                contacto_sec = :contacto_sec,
                dire_pro = :dire_pro,
                ciudad_pro = :ciudad_pro,
                tel_pro = :tel_pro,
                tel_sec = :tel_sec,
                email_pro = :email_pro,
                desc_pro = :desc_pro
             WHERE cod_pro = :cod_pro'
        );
        $data['cod_pro'] = $codPro;
        $stmt->execute($data);
    }

    public function delete(int $codPro): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM proveedor WHERE cod_pro = :cod');
        $stmt->execute(['cod' => $codPro]);
    }
}
