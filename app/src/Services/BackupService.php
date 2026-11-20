<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use RuntimeException;

final class BackupService
{
    /**
     * @param array{host:string,port:int,name:string,user:string,pass:string} $db
     */
    public function __construct(
        private readonly PDO $pdo,
        private readonly array $db,
    ) {
    }

    /**
     * Escribe un dump SQL lógico a un archivo temporal.
     *
     * @return array{filename:string,path:string}
     */
    public function createDumpFile(?string $label = null): array
    {
        @set_time_limit(0);

        $label = $this->sanitizeLabel($label);
        $stamp = date('Y-m-d_His');
        $filename = $label !== ''
            ? sprintf('carioca_%s_%s.sql', $label, $stamp)
            : sprintf('carioca_%s.sql', $stamp);

        $path = tempnam(sys_get_temp_dir(), 'carioca_bak_');
        if ($path === false) {
            throw new RuntimeException('No se pudo crear archivo temporal.', 500);
        }

        try {
            if ($this->tryMysqldump($path)) {
                return ['filename' => $filename, 'path' => $path];
            }
            $this->writePhpDump($path);
            return ['filename' => $filename, 'path' => $path];
        } catch (\Throwable $e) {
            @unlink($path);
            throw $e;
        }
    }

    private function tryMysqldump(string $path): bool
    {
        $bin = $this->findDumpBinary();
        if ($bin === null) {
            return false;
        }

        $cmd = [
            $bin,
            '--host=' . $this->db['host'],
            '--port=' . (string) (int) $this->db['port'],
            '--user=' . $this->db['user'],
            '--password=' . $this->db['pass'],
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--default-character-set=utf8mb4',
            '--result-file=' . $path,
            $this->db['name'],
        ];

        $descriptor = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $proc = proc_open($cmd, $descriptor, $pipes, null, null, ['bypass_shell' => true]);
        if (!is_resource($proc)) {
            return false;
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]) ?: '';
        $stderr = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($proc);

        if ($code !== 0 || !is_file($path) || filesize($path) === 0) {
            // Vaciar y dejar el path para el fallback PHP.
            file_put_contents($path, '');
            return false;
        }

        unset($stdout, $stderr);
        return true;
    }

    private function findDumpBinary(): ?string
    {
        foreach (['mysqldump', 'mariadb-dump'] as $name) {
            $out = [];
            $code = 0;
            @exec('command -v ' . escapeshellarg($name) . ' 2>/dev/null', $out, $code);
            if ($code === 0 && isset($out[0]) && $out[0] !== '') {
                return $out[0];
            }
        }
        return null;
    }

    private function writePhpDump(string $path): void
    {
        $fh = fopen($path, 'wb');
        if ($fh === false) {
            throw new RuntimeException('No se pudo abrir archivo temporal.', 500);
        }

        try {
            $dbName = (string) $this->pdo->query('SELECT DATABASE()')->fetchColumn();
            $tables = $this->pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
            if ($tables === false || $tables === []) {
                throw new RuntimeException('No hay tablas para respaldar.', 500);
            }

            $this->w($fh, "-- CARIOCA backup\n");
            $this->w($fh, '-- Database: ' . $dbName . "\n");
            $this->w($fh, '-- Generated: ' . date('c') . "\n");
            $this->w($fh, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

            $prevBuffered = $this->pdo->getAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY);
            $this->pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

            try {
                foreach ($tables as $table) {
                    $table = (string) $table;
                    $safe = str_replace('`', '``', $table);
                    $create = $this->pdo->query('SHOW CREATE TABLE `' . $safe . '`')->fetch(PDO::FETCH_ASSOC);
                    if ($create === false) {
                        continue;
                    }
                    $createSql = $create['Create Table'] ?? $create['Create View'] ?? null;
                    if ($createSql === null) {
                        continue;
                    }

                    $this->w($fh, "-- --------------------------------------------------------\n");
                    $this->w($fh, '-- Table: `' . $table . "`\n");
                    $this->w($fh, 'DROP TABLE IF EXISTS `' . $safe . "`;\n");
                    $this->w($fh, $createSql . ";\n\n");

                    if (isset($create['Create View'])) {
                        continue;
                    }

                    $stmt = $this->pdo->query('SELECT * FROM `' . $safe . '`');
                    if ($stmt === false) {
                        continue;
                    }

                    $batch = [];
                    $cols = null;
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        if ($cols === null) {
                            $cols = array_keys($row);
                        }
                        $vals = [];
                        foreach ($cols as $col) {
                            $vals[] = $this->sqlValue($row[$col]);
                        }
                        $batch[] = '(' . implode(',', $vals) . ')';
                        if (count($batch) >= 200) {
                            $this->w($fh, $this->insertStatement($table, $cols, $batch) . "\n");
                            $batch = [];
                        }
                    }
                    $stmt->closeCursor();
                    if ($batch !== [] && $cols !== null) {
                        $this->w($fh, $this->insertStatement($table, $cols, $batch) . "\n");
                    }
                    $this->w($fh, "\n");
                }
            } finally {
                $this->pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, (bool) $prevBuffered);
            }

            $this->w($fh, "SET FOREIGN_KEY_CHECKS=1;\n-- End of backup\n");
            fclose($fh);
        } catch (\Throwable $e) {
            fclose($fh);
            throw $e;
        }
    }

    private function sanitizeLabel(?string $label): string
    {
        $label = trim((string) $label);
        if ($label === '') {
            return '';
        }
        $label = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $label) ?? '';
        return substr($label, 0, 40);
    }

    /**
     * @param resource $fh
     */
    private function w($fh, string $chunk): void
    {
        if (fwrite($fh, $chunk) === false) {
            throw new RuntimeException('Error escribiendo respaldo.', 500);
        }
    }

    /**
     * @param list<string> $cols
     * @param list<string> $batch
     */
    private function insertStatement(string $table, array $cols, array $batch): string
    {
        $colList = implode(',', array_map(
            static fn (string $c): string => '`' . str_replace('`', '``', $c) . '`',
            $cols
        ));
        $tableSql = '`' . str_replace('`', '``', $table) . '`';
        return 'INSERT INTO ' . $tableSql . ' (' . $colList . ") VALUES\n" . implode(",\n", $batch) . ';';
    }

    private function sqlValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        $quoted = $this->pdo->quote((string) $value);
        return $quoted === false ? 'NULL' : $quoted;
    }
}
