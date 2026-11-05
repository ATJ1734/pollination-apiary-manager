<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\PlacementRepository;
use App\Support\AuditLog;

final class PlacementController extends Controller
{
    public function remove(array $params): void
    {
        $this->requireCsrf();
        $id = (int) $params['id'];
        $repo = new PlacementRepository();
        $placement = $repo->find($id);
        if ($placement === null) {
            (new ErrorController())->notFound();
            return;
        }

        try {
            $repo->remove($id, date('Y-m-d'));
            AuditLog::record('update', 'placement', $id, "Placement #{$id} removed.");
            $this->flash('success', 'Hive removed from contract.');
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }

        $this->redirect($_SERVER['HTTP_REFERER'] ?? "/contracts/{$placement['contract_id']}");
    }
}
