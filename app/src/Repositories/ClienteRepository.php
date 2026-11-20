<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\ListQuery;
use PDO;

final class ClienteRepository
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
            $where = 'WHERE nom_cli LIKE :q1 OR ciudad_cli LIKE :q2 OR email_cli LIKE :q3 OR tel_cli LIKE :q4 OR CAST(cod_cli AS CHAR) LIKE :q5';
            $like = '%' . $query->q . '%';
            $params = [
                'q1' => $like,
                'q2' => $like,
                'q3' => $like,
                'q4' => $like,
                'q5' => $like,
            ];
        }

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM cliente $where");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT cod_cli, nom_cli, contacto_sec, dire_cli, dire_sec, ciudad_cli, tel_cli, tel_sec, email_cli, desc_cli
                FROM cliente
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

    public function find(int $codCli): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT cod_cli, nom_cli, contacto_sec, dire_cli, dire_sec, ciudad_cli, tel_cli, tel_sec, email_cli, desc_cli
             FROM cliente WHERE cod_cli = :cod LIMIT 1'
        );
        $stmt->execute(['cod' => $codCli]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function nextCode(): int
    {
        $max = (int) $this->pdo->query('SELECT COALESCE(MAX(cod_cli), 0) FROM cliente')->fetchColumn();
        return $max + 1;
    }

    public function insert(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO cliente (nom_cli, contacto_sec, dire_cli, dire_sec, ciudad_cli, tel_cli, tel_sec, email_cli, desc_cli)
             VALUES (:nom_cli, :contacto_sec, :dire_cli, :dire_sec, :ciudad_cli, :tel_cli, :tel_sec, :email_cli, :desc_cli)'
        );
        $stmt->execute($data);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $codCli, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE cliente SET
                nom_cli = :nom_cli,
                contacto_sec = :contacto_sec,
                dire_cli = :dire_cli,
                dire_sec = :dire_sec,
                ciudad_cli = :ciudad_cli,
                tel_cli = :tel_cli,
                tel_sec = :tel_sec,
                email_cli = :email_cli,
                desc_cli = :desc_cli
             WHERE cod_cli = :cod_cli'
        );
        $data['cod_cli'] = $codCli;
        $stmt->execute($data);
    }

    public function delete(int $codCli): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM cliente WHERE cod_cli = :cod');
        $stmt->execute(['cod' => $codCli]);
    }
}
