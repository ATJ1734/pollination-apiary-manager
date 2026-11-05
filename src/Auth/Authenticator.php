<?php

declare(strict_types=1);

namespace App\Auth;

use App\Support\Database;
use PDO;

/** Session login/logout plus the 5-tries/15-minute lockout rule. */
final class Authenticator
{
    private const MAX_FAILED_LOGINS = 5;
    private const LOCKOUT_MINUTES = 15;

    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    /** @return array{ok: bool, error?: string} */
    public function attempt(string $email, string $password): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, email, password_hash, role, active, failed_logins, locked_until
             FROM users WHERE email = ?'
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        if ($row === false) {
            return ['ok' => false, 'error' => 'Invalid email or password.'];
        }

        if ($row['locked_until'] !== null && new \DateTimeImmutable($row['locked_until']) > new \DateTimeImmutable()) {
            return ['ok' => false, 'error' => 'Account locked. Try again in a few minutes.'];
        }

        if (!$row['active']) {
            return ['ok' => false, 'error' => 'This account is disabled.'];
        }

        if (!password_verify($password, $row['password_hash'])) {
            $this->recordFailedLogin((int) $row['id'], (int) $row['failed_logins']);

            return ['ok' => false, 'error' => 'Invalid email or password.'];
        }

        $this->db->prepare('UPDATE users SET failed_logins = 0, locked_until = NULL WHERE id = ?')
            ->execute([$row['id']]);

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'role' => $row['role'],
        ];

        return ['ok' => true];
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }

    private function recordFailedLogin(int $userId, int $currentFailures): void
    {
        $failures = $currentFailures + 1;
        $lockedUntil = null;

        if ($failures >= self::MAX_FAILED_LOGINS) {
            $lockedUntil = (new \DateTimeImmutable())
                ->modify('+' . self::LOCKOUT_MINUTES . ' minutes')
                ->format('Y-m-d H:i:s');
            $failures = 0;
        }

        $this->db->prepare('UPDATE users SET failed_logins = ?, locked_until = ? WHERE id = ?')
            ->execute([$failures, $lockedUntil, $userId]);
    }
}
