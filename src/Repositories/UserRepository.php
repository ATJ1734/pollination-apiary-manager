<?php

declare(strict_types=1);

namespace App\Repositories;

final class UserRepository extends BaseRepository
{
    protected string $table = 'users';
    protected array $searchable = ['name', 'email'];
    protected array $sortable = ['name', 'email', 'role', 'active', 'created_at'];
    protected string $defaultSort = 'name';
    protected string $defaultDirection = 'asc';

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $stmt = $this->run('SELECT COUNT(*) FROM users WHERE email = ? AND id != ?', [$email, $excludeId]);
        } else {
            $stmt = $this->run('SELECT COUNT(*) FROM users WHERE email = ?', [$email]);
        }

        return (int) $stmt->fetchColumn() > 0;
    }
}
