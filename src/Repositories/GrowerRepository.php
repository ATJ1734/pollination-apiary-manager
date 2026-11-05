<?php

declare(strict_types=1);

namespace App\Repositories;

final class GrowerRepository extends BaseRepository
{
    protected string $table = 'growers';
    protected array $searchable = ['farm_name', 'contact_name', 'phone', 'email'];
    protected array $sortable = ['farm_name', 'created_at'];
    protected string $defaultSort = 'farm_name';
    protected string $defaultDirection = 'asc';

    /** @return list<array{id:int,farm_name:string}> for dropdowns. */
    public function all(): array
    {
        return $this->run('SELECT id, farm_name FROM growers ORDER BY farm_name')->fetchAll();
    }
}
