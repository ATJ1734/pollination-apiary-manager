<?php

declare(strict_types=1);

namespace App\Repositories;

final class HiveRepository extends BaseRepository
{
    protected string $table = 'hives';
    protected array $searchable = ['h.hive_code', 'a.name'];
    protected array $sortable = ['hive_code', 'status', 'created_at'];
    protected string $defaultSort = 'hive_code';
    protected string $defaultDirection = 'asc';

    /**
     * Overridden (rather than the base paginate()) because the list needs the
     * apiary name and a status filter alongside the free-text search.
     *
     * @return array{rows: list<array>, total: int, page: int, perPage: int}
     */
    public function paginateWithApiary(array $params, int $page, int $perPage): array
    {
        $conditions = [];
        $bindings = [];

        $q = trim((string) ($params['q'] ?? ''));
        if ($q !== '') {
            $conditions[] = '(h.hive_code LIKE :q0 OR a.name LIKE :q1)';
            $bindings['q0'] = "%{$q}%";
            $bindings['q1'] = "%{$q}%";
        }

        $status = (string) ($params['status'] ?? '');
        if ($status !== '') {
            $conditions[] = 'h.status = :status';
            $bindings['status'] = $status;
        }

        $apiaryId = (int) ($params['apiary_id'] ?? 0);
        if ($apiaryId > 0) {
            $conditions[] = 'h.apiary_id = :apiary_id';
            $bindings['apiary_id'] = $apiaryId;
        }

        $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);
        $sort = in_array($params['sort'] ?? '', $this->sortable, true) ? 'h.' . $params['sort'] : 'h.hive_code';
        $dir = strtolower($params['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

        $from = 'FROM hives h JOIN apiaries a ON a.id = h.apiary_id' . $where;
        $total = (int) $this->run("SELECT COUNT(*) {$from}", $bindings)->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT h.*, a.name AS apiary_name {$from} ORDER BY {$sort} {$dir} LIMIT {$perPage} OFFSET {$offset}";
        $rows = $this->run($sql, $bindings)->fetchAll();

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage];
    }

    public function findWithApiary(int $id): ?array
    {
        $row = $this->run(
            'SELECT h.*, a.name AS apiary_name FROM hives h JOIN apiaries a ON a.id = h.apiary_id WHERE h.id = ?',
            [$id],
        )->fetch();

        return $row === false ? null : $row;
    }

    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $stmt = $this->run('SELECT COUNT(*) FROM hives WHERE hive_code = ? AND id != ?', [$code, $excludeId]);
        } else {
            $stmt = $this->run('SELECT COUNT(*) FROM hives WHERE hive_code = ?', [$code]);
        }

        return (int) $stmt->fetchColumn() > 0;
    }

    /** The hive's most recent inspection, or null if it has none yet. */
    public function latestInspection(int $hiveId): ?array
    {
        $row = $this->run(
            'SELECT * FROM inspections WHERE hive_id = ? ORDER BY inspected_on DESC, id DESC LIMIT 1',
            [$hiveId],
        )->fetch();

        return $row === false ? null : $row;
    }

    /** @return list<array> newest first, for the hive detail page's timeline and chart. */
    public function inspectionHistory(int $hiveId): array
    {
        return $this->run(
            'SELECT i.*, u.name AS inspector_name FROM inspections i
             JOIN users u ON u.id = i.inspector_user_id
             WHERE i.hive_id = ? ORDER BY i.inspected_on DESC, i.id DESC',
            [$hiveId],
        )->fetchAll();
    }

    /** @return list<array> newest first. */
    public function treatmentHistory(int $hiveId): array
    {
        return $this->run(
            'SELECT * FROM treatments WHERE hive_id = ? ORDER BY applied_on DESC, id DESC',
            [$hiveId],
        )->fetchAll();
    }

    /** @return list<array> newest first, joined to contract/grower for display. */
    public function placementHistory(int $hiveId): array
    {
        return $this->run(
            'SELECT p.*, c.crop, c.season_year, g.farm_name
             FROM placements p
             JOIN contracts c ON c.id = p.contract_id
             JOIN growers g ON g.id = c.grower_id
             WHERE p.hive_id = ? ORDER BY p.placed_on DESC, p.id DESC',
            [$hiveId],
        )->fetchAll();
    }
}
