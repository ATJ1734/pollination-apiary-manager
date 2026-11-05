<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Validation\DateRules;
use RuntimeException;

final class PlacementRepository extends BaseRepository
{
    protected string $table = 'placements';

    /**
     * The contract planner's core query: which hives could fill this contract
     * right now. One method, one query, each condition commented, per the brief.
     *
     * @return list<array>
     */
    public function eligibleHivesForContract(int $contractId, int $minFramesOfBees, float $miteThreshold, int $overdueDays): array
    {
        $sql = <<<SQL
            SELECT h.id AS hive_id, h.hive_code, a.name AS apiary_name,
                   li.frames_of_bees, li.mites_per_300, li.inspected_on,
                   DATEDIFF(CURDATE(), li.inspected_on) AS days_since_inspection
            FROM hives h
            JOIN apiaries a ON a.id = h.apiary_id
            -- latest inspection per hive, via a window function (MySQL 8)
            JOIN (
                SELECT hive_id, frames_of_bees, mites_per_300, inspected_on,
                       ROW_NUMBER() OVER (PARTITION BY hive_id ORDER BY inspected_on DESC, id DESC) AS rn
                FROM inspections
            ) li ON li.hive_id = h.id AND li.rn = 1
            WHERE h.status = 'active'
              -- inspected recently enough that the numbers below can be trusted
              AND li.inspected_on >= DATE_SUB(CURDATE(), INTERVAL :overdue_days DAY)
              -- strong enough for this contract's minimum colony strength
              AND li.frames_of_bees >= :min_frames
              -- mite load at or below the threshold (no reading yet counts as fine)
              AND (li.mites_per_300 IS NULL OR li.mites_per_300 / 3 <= :mite_threshold)
              -- not already committed to this contract, or still out on another one
              AND h.id NOT IN (
                  SELECT hive_id FROM placements
                  WHERE contract_id = :contract_id OR removed_on IS NULL
              )
            ORDER BY a.name, h.hive_code
            SQL;

        return $this->run($sql, [
            'overdue_days' => $overdueDays,
            'min_frames' => $minFramesOfBees,
            'mite_threshold' => $miteThreshold,
            'contract_id' => $contractId,
        ])->fetchAll();
    }

    /**
     * True if placing this hive over [placedOn, removedOn] would overlap an
     * existing placement for the same hive. A null removedOn means "still
     * placed," i.e. open-ended into the future.
     */
    public function hasOverlap(int $hiveId, string $placedOn, ?string $removedOn, ?int $excludePlacementId = null): bool
    {
        $farFuture = '2999-12-31';
        $newEnd = $removedOn ?? $farFuture;

        $sql = 'SELECT placed_on, removed_on FROM placements WHERE hive_id = ?';
        $bindings = [$hiveId];
        if ($excludePlacementId !== null) {
            $sql .= ' AND id != ?';
            $bindings[] = $excludePlacementId;
        }

        foreach ($this->run($sql, $bindings)->fetchAll() as $existing) {
            $existingEnd = $existing['removed_on'] ?? $farFuture;
            if ($placedOn <= $existingEnd && $existing['placed_on'] <= $newEnd) {
                return true;
            }
        }

        return false;
    }

    /**
     * Assigns every hive in $hiveIds to $contractId as of $placedOn, all or
     * nothing: re-validates overlap and the minimum-frames rule for each hive
     * inside one transaction, and rolls back entirely if any of them fail.
     *
     * @param list<int> $hiveIds
     * @return list<int> the new placement ids
     */
    public function assignBulk(int $contractId, array $hiveIds, string $placedOn, int $minFramesOfBees): array
    {
        $hiveRepo = new HiveRepository();
        $this->db->beginTransaction();

        try {
            $newIds = [];
            foreach ($hiveIds as $hiveId) {
                $hive = $hiveRepo->find($hiveId);
                if ($hive === null || !\App\Support\HiveStatus::canReceiveActivity($hive['status'])) {
                    throw new RuntimeException("Hive #{$hiveId} can't receive a new placement.");
                }

                $latest = $hiveRepo->latestInspection($hiveId);
                if ($latest === null || (int) $latest['frames_of_bees'] < $minFramesOfBees) {
                    throw new RuntimeException("Hive #{$hiveId} doesn't meet this contract's minimum frames of bees.");
                }

                if ($this->hasOverlap($hiveId, $placedOn, null)) {
                    throw new RuntimeException("Hive #{$hiveId} already has an overlapping placement.");
                }

                $newIds[] = $this->create([
                    'contract_id' => $contractId,
                    'hive_id' => $hiveId,
                    'placed_on' => $placedOn,
                    'removed_on' => null,
                    'frames_at_placement' => $latest['frames_of_bees'],
                ]);
            }

            $this->db->commit();

            return $newIds;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function remove(int $placementId, string $removedOn): void
    {
        $placement = $this->find($placementId);
        if ($placement === null) {
            throw new RuntimeException('Placement not found.');
        }
        if (!DateRules::onOrAfter($removedOn, $placement['placed_on'])) {
            throw new RuntimeException("Removed date can't come before the placed date.");
        }

        $this->update($placementId, ['removed_on' => $removedOn]);
    }
}
