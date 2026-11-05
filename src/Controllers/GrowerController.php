<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\BaseRepository;
use App\Repositories\GrowerRepository;

final class GrowerController extends CrudController
{
    protected function repository(): BaseRepository
    {
        return new GrowerRepository();
    }

    protected function rules(): array
    {
        return [
            'farm_name' => 'required|max:120',
            'contact_name' => 'nullable|max:120',
            'phone' => 'nullable|max:30',
            'email' => 'nullable|email|max:190',
            'address' => 'nullable|max:255',
            'notes' => 'nullable|max:2000',
        ];
    }

    protected function entityName(): string
    {
        return 'grower';
    }

    protected function viewPrefix(): string
    {
        return 'growers';
    }

    protected function basePath(): string
    {
        return '/growers';
    }

    protected function fields(): array
    {
        return ['farm_name', 'contact_name', 'phone', 'email', 'address', 'notes'];
    }

    protected function label(array $data): string
    {
        return (string) ($data['farm_name'] ?? '');
    }
}
