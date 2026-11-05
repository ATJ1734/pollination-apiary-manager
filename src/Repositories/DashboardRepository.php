<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Database;
use PDO;

/**
 * Every query here is written to be checked by hand against a plain SQL
 * client -- the milestone 6 check is "each tile's number matches a
 * hand-written SQL query" -- so each one stays a single, readable statement.
 */
final class DashboardRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    public function activeHiveCount(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM hives WHERE status = 'active'")->fetchColumn();
    }

    public function activeContractCount(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM contracts WHERE status = 'active'")->fetchColumn();
    }

    /** Hives whose most recent mite wash is above the threshold (mites_per_300 / 3). */
    public function hivesOverMiteThreshold(float $threshold): int
    {
        $sql = "SELECT COUNT(*) FROM (
                    SELECT i.hive_id, i.mites_per_300,
                           ROW_NUMBER() OVER (PARTITION BY i.hive_id ORDER BY i.inspected_on DESC, i.id DESC) AS rn
                    FROM inspections i
                    JOIN hives h ON h.id = i.hive_id
                    WHERE h.status NOT IN ('dead','sold','combined')
                ) latest
                WHERE rn = 1 AND latest.mites_per_300 IS NOT NULL AND latest.mites_per_300 / 3 > :threshold";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['threshold' => $threshold]);

        return (int) $stmt->fetchColumn();
    }

    /** Live hives whose latest inspection is more than $days old, or that have never been inspected. */
    public function hivesNotInspectedWithin(int $days): int
    {
        $sql = "SELECT COUNT(*) FROM hives h
                WHERE h.status NOT IN ('dead','sold','combined')
                  AND NOT EXISTS (
                      SELECT 1 FROM inspections i
                      WHERE i.hive_id = h.id AND i.inspected_on >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
                  )";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['days' => $days]);

        return (int) $stmt->fetchColumn();
    }

    /** Sum of (hives_required - hives currently placed) for contracts starting in the next $days, floored at 0 per contract. */
    public function hiveShortfallNextDays(int $days): int
    {
        $sql = "SELECT COALESCE(SUM(GREATEST(0, c.hives_required - placed.cnt)), 0)
                FROM contracts c
                LEFT JOIN (
                    SELECT contract_id, COUNT(*) AS cnt FROM placements WHERE removed_on IS NULL GROUP BY contract_id
                ) placed ON placed.contract_id = c.id
                WHERE c.start_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['days' => $days]);

        return (int) $stmt->fetchColumn();
    }

    /** @return list<array{hive_id:int,hive_code:string,apiary_name:string,mite_load:float}> */
    public function miteAlerts(float $threshold, int $limit = 10): array
    {
        $sql = "SELECT h.id AS hive_id, h.hive_code, a.name AS apiary_name, latest.mites_per_300 / 3 AS mite_load
                FROM (
                    SELECT hive_id, mites_per_300,
                           ROW_NUMBER() OVER (PARTITION BY hive_id ORDER BY inspected_on DESC, id DESC) AS rn
                    FROM inspections
                ) latest
                JOIN hives h ON h.id = latest.hive_id AND latest.rn = 1
                JOIN apiaries a ON a.id = h.apiary_id
                WHERE h.status NOT IN ('dead','sold','combined')
                  AND latest.mites_per_300 IS NOT NULL AND latest.mites_per_300 / 3 > :threshold
                ORDER BY mite_load DESC
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('threshold', $threshold);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** @return list<array{hive_id:int,hive_code:string,apiary_name:string,days_overdue:int|null}> */
    public function overdueInspections(int $days, int $limit = 10): array
    {
        $sql = "SELECT h.id AS hive_id, h.hive_code, a.name AS apiary_name,
                       DATEDIFF(CURDATE(), (SELECT MAX(inspected_on) FROM inspections WHERE hive_id = h.id)) AS days_overdue
                FROM hives h
                JOIN apiaries a ON a.id = h.apiary_id
                WHERE h.status NOT IN ('dead','sold','combined')
                  AND NOT EXISTS (
                      SELECT 1 FROM inspections i
                      WHERE i.hive_id = h.id AND i.inspected_on >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
                  )
                ORDER BY days_overdue DESC
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('days', $days, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** @return list<array{hive_code:string,product:string,remove_by:string}> */
    public function overdueTreatments(int $limit = 10): array
    {
        $sql = "SELECT h.hive_code, t.product, t.remove_by
                FROM treatments t JOIN hives h ON h.id = t.hive_id
                WHERE t.removed_on IS NULL AND t.remove_by < CURDATE()
                ORDER BY t.remove_by ASC
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** Average mite load (per 100 bees) per apiary, one point per calendar month, last 12 months. */
    public function miteLoadByApiaryByMonth(): array
    {
        $sql = "SELECT a.name AS apiary_name, DATE_FORMAT(i.inspected_on, '%Y-%m') AS month,
                       AVG(i.mites_per_300) / 3 AS avg_mite_load
                FROM inspections i
                JOIN hives h ON h.id = i.hive_id
                JOIN apiaries a ON a.id = h.apiary_id
                WHERE i.mites_per_300 IS NOT NULL
                  AND i.inspected_on >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY a.name, month
                ORDER BY month, a.name";

        return $this->db->query($sql)->fetchAll();
    }

    /** Hives bucketed by their latest inspection's frames of bees. */
    public function colonyStrengthSpread(): array
    {
        $sql = "SELECT
                    CASE
                        WHEN latest.frames_of_bees BETWEEN 0 AND 4 THEN '0-4'
                        WHEN latest.frames_of_bees BETWEEN 5 AND 8 THEN '5-8'
                        WHEN latest.frames_of_bees BETWEEN 9 AND 12 THEN '9-12'
                        WHEN latest.frames_of_bees BETWEEN 13 AND 16 THEN '13-16'
                        ELSE '17+'
                    END AS bucket,
                    COUNT(*) AS hive_count
                FROM (
                    SELECT hive_id, frames_of_bees,
                           ROW_NUMBER() OVER (PARTITION BY hive_id ORDER BY inspected_on DESC, id DESC) AS rn
                    FROM inspections
                ) latest
                JOIN hives h ON h.id = latest.hive_id AND latest.rn = 1
                WHERE h.status NOT IN ('dead','sold','combined')
                GROUP BY bucket";

        $rows = $this->db->query($sql)->fetchAll(PDO::FETCH_KEY_PAIR);
        $order = ['0-4', '5-8', '9-12', '13-16', '17+'];

        return array_map(static fn ($b) => (int) ($rows[$b] ?? 0), $order);
    }

    /** @return list<array{label:string,required:int,placed:int}> contracts that are signed/active/completed, newest first. */
    public function contractsFilledVsRequired(int $limit = 10): array
    {
        $sql = "SELECT g.farm_name, c.crop, c.season_year, c.hives_required,
                       (SELECT COUNT(*) FROM placements p WHERE p.contract_id = c.id AND p.removed_on IS NULL) AS placed
                FROM contracts c JOIN growers g ON g.id = c.grower_id
                WHERE c.status IN ('signed', 'active', 'completed')
                ORDER BY c.start_date DESC
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(static fn ($r) => [
            'label' => ucfirst($r['crop']) . ' ' . $r['season_year'] . ' (' . $r['farm_name'] . ')',
            'required' => (int) $r['hives_required'],
            'placed' => (int) $r['placed'],
        ], $stmt->fetchAll());
    }
}
