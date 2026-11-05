<?php

declare(strict_types=1);

namespace App\Repositories;

final class ContractRepository extends BaseRepository
{
    protected string $table = 'contracts';
    protected array $sortable = ['start_date', 'status', 'crop', 'season_year'];
    protected string $defaultSort = 'start_date';
    protected string $defaultDirection = 'desc';

    /** @return array{rows: list<array>, total: int, page: int, perPage: int} */
    public function paginateWithGrower(array $params, int $page, int $perPage): array
    {
        $conditions = [];
        $bindings = [];

        $q = trim((string) ($params['q'] ?? ''));
        if ($q !== '') {
            $conditions[] = '(g.farm_name LIKE :q0 OR c.crop LIKE :q1)';
            $bindings['q0'] = "%{$q}%";
            $bindings['q1'] = "%{$q}%";
        }

        $status = (string) ($params['status'] ?? '');
        if ($status !== '') {
            $conditions[] = 'c.status = :status';
            $bindings['status'] = $status;
        }

        $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);
        $sort = in_array($params['sort'] ?? '', $this->sortable, true) ? 'c.' . $params['sort'] : 'c.start_date';
        $dir = strtolower($params['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

        $from = 'FROM contracts c JOIN growers g ON g.id = c.grower_id' . $where;
        $total = (int) $this->run("SELECT COUNT(*) {$from}", $bindings)->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT c.*, g.farm_name,
                       (SELECT COUNT(*) FROM placements p WHERE p.contract_id = c.id AND p.removed_on IS NULL) AS hives_placed
                {$from} ORDER BY {$sort} {$dir}, c.id DESC LIMIT {$perPage} OFFSET {$offset}";
        $rows = $this->run($sql, $bindings)->fetchAll();

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage];
    }

    public function findWithGrower(int $id): ?array
    {
        $row = $this->run(
            'SELECT c.*, g.farm_name, g.contact_name, g.phone, g.email, g.address
             FROM contracts c JOIN growers g ON g.id = c.grower_id WHERE c.id = ?',
            [$id],
        )->fetch();

        return $row === false ? null : $row;
    }

    /** Placements currently filling this contract (not yet removed). */
    public function activePlacements(int $contractId): array
    {
        return $this->run(
            'SELECT p.*, h.hive_code, a.name AS apiary_name
             FROM placements p
             JOIN hives h ON h.id = p.hive_id
             JOIN apiaries a ON a.id = h.apiary_id
             WHERE p.contract_id = ? AND p.removed_on IS NULL
             ORDER BY a.name, h.hive_code',
            [$contractId],
        )->fetchAll();
    }

    public function placedCount(int $contractId): int
    {
        return (int) $this->run(
            'SELECT COUNT(*) FROM placements WHERE contract_id = ? AND removed_on IS NULL',
            [$contractId],
        )->fetchColumn();
    }
}
