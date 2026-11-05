<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Database;
use PDO;

/**
 * Shared list/search/paginate/sort/CRUD for a single table. Entities with
 * their own business rules (hives, inspections, treatments, contracts) still
 * extend this for the boring parts, but add their own methods for the rest.
 */
abstract class BaseRepository
{
    protected PDO $db;
    protected string $table;
    /** @var list<string> columns the free-text search box matches against */
    protected array $searchable = [];
    /** @var list<string> columns ORDER BY is allowed to use */
    protected array $sortable = ['id'];
    protected string $defaultSort = 'id';
    protected string $defaultDirection = 'desc';

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    /**
     * @param array{q?: string, sort?: string, dir?: string} $params
     * @return array{rows: list<array>, total: int, page: int, perPage: int}
     */
    public function paginate(array $params, int $page, int $perPage): array
    {
        [$whereSql, $bindings] = $this->whereClause($params);

        $sort = in_array($params['sort'] ?? '', $this->sortable, true) ? $params['sort'] : $this->defaultSort;
        $dir = strtolower($params['dir'] ?? $this->defaultDirection) === 'asc' ? 'ASC' : 'DESC';

        $total = (int) $this->run("SELECT COUNT(*) FROM {$this->table} {$whereSql}", $bindings)->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT * FROM {$this->table} {$whereSql} ORDER BY {$sort} {$dir} LIMIT {$perPage} OFFSET {$offset}";
        $rows = $this->run($sql, $bindings)->fetchAll();

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage];
    }

    public function find(int $id): ?array
    {
        $stmt = $this->run("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function create(array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(static fn ($c) => ':' . $c, $columns);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders),
        );
        $this->db->prepare($sql)->execute($data);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $assignments = implode(', ', array_map(static fn ($c) => "{$c} = :{$c}", array_keys($data)));
        $data['id'] = $id;
        $this->db->prepare("UPDATE {$this->table} SET {$assignments} WHERE id = :id")->execute($data);
    }

    public function delete(int $id): void
    {
        $this->run("DELETE FROM {$this->table} WHERE id = ?", [$id]);
    }

    /** @return array{0: string, 1: array<int|string,mixed>} */
    protected function whereClause(array $params): array
    {
        $conditions = [];
        $bindings = [];

        $q = trim((string) ($params['q'] ?? ''));
        if ($q !== '' && $this->searchable !== []) {
            // Native (non-emulated) prepares reject reusing one named placeholder
            // more than once, so each searchable column gets its own :q0, :q1, ...
            $likes = [];
            foreach ($this->searchable as $i => $column) {
                $likes[] = "{$column} LIKE :q{$i}";
                $bindings["q{$i}"] = "%{$q}%";
            }
            $conditions[] = '(' . implode(' OR ', $likes) . ')';
        }

        if ($conditions === []) {
            return ['', []];
        }

        return [' WHERE ' . implode(' AND ', $conditions), $bindings];
    }

    protected function run(string $sql, array $bindings = []): \PDOStatement
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);

        return $stmt;
    }
}
