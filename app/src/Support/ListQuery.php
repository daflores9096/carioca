<?php

declare(strict_types=1);

namespace App\Support;

final class ListQuery
{
    public readonly string $q;
    public readonly int $page;
    public readonly int $perPage;
    public readonly string $sort;
    public readonly string $dir;

    /**
     * @param array<string, mixed> $params
     * @param list<string> $allowedSort
     */
    public function __construct(array $params, array $allowedSort, string $defaultSort)
    {
        $this->q = trim((string) ($params['q'] ?? ''));
        $page = (int) ($params['page'] ?? 1);
        $this->page = max(1, $page);
        $perPage = (int) ($params['per_page'] ?? 25);
        $this->perPage = min(100, max(5, $perPage));

        $sort = (string) ($params['sort'] ?? $defaultSort);
        $this->sort = in_array($sort, $allowedSort, true) ? $sort : $defaultSort;

        $dir = strtolower((string) ($params['dir'] ?? 'asc'));
        $this->dir = $dir === 'desc' ? 'DESC' : 'ASC';
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }
}
