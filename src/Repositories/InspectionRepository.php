<?php

declare(strict_types=1);

namespace App\Repositories;

final class InspectionRepository extends BaseRepository
{
    protected string $table = 'inspections';
    protected array $sortable = ['inspected_on', 'created_at'];
    protected string $defaultSort = 'inspected_on';
    protected string $defaultDirection = 'desc';

    /** @return array{rows: list<array>, total: int, page: int, perPage: int} */
    public function paginateWithHive(array $params, int $page, int $perPage): array
    {
        $conditions = [];
        $bindings = [];

        $q = trim((string) ($params['q'] ?? ''));
        if ($q !== '') {
            $conditions[] = '(h.hive_code LIKE :q0 OR a.name LIKE :q1)';
            $bindings['q0'] = "%{$q}%";
            $bindings['q1'] = "%{$q}%";
        }

        $hiveId = (int) ($params['hive_id'] ?? 0);
        if ($hiveId > 0) {
            $conditions[] = 'i.hive_id = :hive_id';
            $bindings['hive_id'] = $hiveId;
        }

        $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);
        $sort = in_array($params['sort'] ?? '', $this->sortable, true) ? 'i.' . $params['sort'] : 'i.inspected_on';
        $dir = strtolower($params['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

        $from = 'FROM inspections i
                 JOIN hives h ON h.id = i.hive_id
                 JOIN apiaries a ON a.id = h.apiary_id
                 JOIN users u ON u.id = i.inspector_user_id' . $where;

        $total = (int) $this->run("SELECT COUNT(*) {$from}", $bindings)->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT i.*, h.hive_code, a.name AS apiary_name, u.name AS inspector_name
                {$from} ORDER BY {$sort} {$dir}, i.id DESC LIMIT {$perPage} OFFSET {$offset}";
        $rows = $this->run($sql, $bindings)->fetchAll();

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage];
    }

    /** @return list<array{id:int,hive_code:string}> hives that are allowed to receive a new inspection. */
    public function inspectableHives(): array
    {
        return $this->run(
            "SELECT id, hive_code FROM hives WHERE status NOT IN ('dead','sold','combined') ORDER BY hive_code"
        )->fetchAll();
    }
}
