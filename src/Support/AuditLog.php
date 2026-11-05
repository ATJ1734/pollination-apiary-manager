<?php

declare(strict_types=1);

namespace App\Support;

/** Writes the one audit_log row the brief requires for every create/update/delete. */
final class AuditLog
{
    public static function record(string $action, string $entity, int $entityId, string $summary): void
    {
        $userId = current_user()['id'] ?? null;

        Database::connection()
            ->prepare('INSERT INTO audit_log (user_id, action, entity, entity_id, summary) VALUES (?, ?, ?, ?, ?)')
            ->execute([$userId, $action, $entity, $entityId, $summary]);
    }
}
