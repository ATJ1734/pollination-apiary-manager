<?php

declare(strict_types=1);

namespace App\Repositories;

final class TreatmentRepository extends BaseRepository
{
    protected string $table = 'treatments';
    protected array $sortable = ['applied_on', 'remove_by', 'created_at'];
    protected string $defaultSort = 'applied_on';
    protected string $defaultDirection = 'desc';

    /** @return array{rows: list<array>, total: int, page: int, perPage: int} */
    public function paginateWithHive(array $params, int $page, int $perPage): array
    {
        $conditions = [];
        $bindings = [];

        $q = trim((string) ($params['q'] ?? ''));
        if ($q !== '') {
            $conditions[] = '(h.hive_code LIKE :q0 OR t.product LIKE :q1)';
            $bindings['q0'] = "%{$q}%";
            $bindings['q1'] = "%{$q}%";
        }

        $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);
        $sort = in_array($params['sort'] ?? '', $this->sortable, true) ? 't.' . $params['sort'] : 't.applied_on';
        $dir = strtolower($params['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

        $from = 'FROM treatments t JOIN hives h ON h.id = t.hive_id' . $where;
        $total = (int) $this->run("SELECT COUNT(*) {$from}", $bindings)->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT t.*, h.hive_code {$from} ORDER BY {$sort} {$dir}, t.id DESC LIMIT {$perPage} OFFSET {$offset}";
        $rows = $this->run($sql, $bindings)->fetchAll();

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage];
    }

    /** @return list<array{id:int,hive_code:string}> hives allowed to receive a new treatment. */
    public function treatableHives(): array
    {
        return $this->run(
            "SELECT id, hive_code FROM hives WHERE status NOT IN ('dead','sold','combined') ORDER BY hive_code"
        )->fetchAll();
    }
}
