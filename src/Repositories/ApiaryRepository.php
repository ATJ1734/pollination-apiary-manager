<?php

declare(strict_types=1);

namespace App\Repositories;

final class ApiaryRepository extends BaseRepository
{
    protected string $table = 'apiaries';
    protected array $searchable = ['name', 'location_description', 'landowner_name'];
    protected array $sortable = ['name', 'active', 'created_at'];
    protected string $defaultSort = 'name';
    protected string $defaultDirection = 'asc';

    /** @return list<array{id:int,name:string}> active apiaries, for dropdowns. */
    public function allActive(): array
    {
        return $this->run('SELECT id, name FROM apiaries WHERE active = 1 ORDER BY name')->fetchAll();
    }

    public function hiveCount(int $apiaryId): int
    {
        return (int) $this->run('SELECT COUNT(*) FROM hives WHERE apiary_id = ?', [$apiaryId])->fetchColumn();
    }
}
