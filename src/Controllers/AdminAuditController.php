<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\Database;

final class AdminAuditController extends Controller
{
    public function index(): void
    {
        $page = max(1, (int) $this->input('page', 1));
        $perPage = (int) config('app.per_page');

        $conditions = [];
        $bindings = [];

        $entity = (string) $this->input('entity', '');
        if ($entity !== '') {
            $conditions[] = 'a.entity = :entity';
            $bindings['entity'] = $entity;
        }
        $action = (string) $this->input('action', '');
        if ($action !== '') {
            $conditions[] = 'a.action = :action';
            $bindings['action'] = $action;
        }

        $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);
        $db = Database::connection();

        $countStmt = $db->prepare("SELECT COUNT(*) FROM audit_log a{$where}");
        $countStmt->execute($bindings);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT a.*, u.name AS user_name FROM audit_log a LEFT JOIN users u ON u.id = a.user_id
                {$where} ORDER BY a.created_at DESC, a.id DESC LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $db->prepare($sql);
        $stmt->execute($bindings);

        $this->view('admin/audit_index', [
            'rows' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'entity' => $entity,
            'action' => $action,
            'entities' => ['apiary', 'hive', 'inspection', 'treatment', 'grower', 'contract', 'placement', 'user'],
        ]);
    }
}
