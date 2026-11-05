<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ContractRepository;
use App\Repositories\PlacementRepository;

final class PlannerController extends Controller
{
    public function show(array $params): void
    {
        $contractRepo = new ContractRepository();
        $contract = $contractRepo->findWithGrower((int) $params['id']);
        if ($contract === null) {
            (new ErrorController())->notFound();
            return;
        }

        $placedCount = $contractRepo->placedCount($contract['id']);
        $eligible = (new PlacementRepository())->eligibleHivesForContract(
            $contract['id'],
            (int) $contract['min_frames_of_bees'],
            (float) config('app.mite_threshold'),
            (int) config('app.inspection_overdue_days'),
        );

        $this->view('contracts/planner', [
            'contract' => $contract,
            'placedCount' => $placedCount,
            'shortfall' => max(0, (int) $contract['hives_required'] - $placedCount),
            'eligible' => $eligible,
        ]);
    }

    public function assign(array $params): void
    {
        $this->requireCsrf();
        $contractId = (int) $params['id'];
        $contractRepo = new ContractRepository();
        $contract = $contractRepo->find($contractId);
        if ($contract === null) {
            (new ErrorController())->notFound();
            return;
        }

        $hiveIds = array_map('intval', (array) $this->input('hive_ids', []));
        $hiveIds = array_values(array_unique(array_filter($hiveIds)));

        if ($hiveIds === []) {
            $this->flash('error', 'Select at least one hive to assign.');
            $this->redirect("/contracts/{$contractId}/planner");
        }

        try {
            $placed = (new PlacementRepository())->assignBulk(
                $contractId,
                $hiveIds,
                date('Y-m-d'),
                (int) $contract['min_frames_of_bees'],
            );
            \App\Support\AuditLog::record(
                'create',
                'placement',
                $contractId,
                count($placed) . " hive(s) assigned to contract #{$contractId}.",
            );
            $this->flash('success', count($placed) . ' hive(s) assigned.');
        } catch (\Throwable $e) {
            $this->flash('error', 'Could not assign hives: ' . $e->getMessage());
        }

        $this->redirect("/contracts/{$contractId}/planner");
    }
}
