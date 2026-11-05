<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ApiaryRepository;
use App\Repositories\BaseRepository;

final class ApiaryController extends CrudController
{
    protected function repository(): BaseRepository
    {
        return new ApiaryRepository();
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|max:120',
            'location_description' => 'nullable|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'landowner_name' => 'nullable|max:120',
            'landowner_phone' => 'nullable|max:30',
            'notes' => 'nullable|max:2000',
        ];
    }

    protected function entityName(): string
    {
        return 'apiary';
    }

    protected function viewPrefix(): string
    {
        return 'apiaries';
    }

    protected function basePath(): string
    {
        return '/apiaries';
    }

    protected function fields(): array
    {
        return ['name', 'location_description', 'latitude', 'longitude', 'landowner_name', 'landowner_phone', 'notes', 'active'];
    }

    protected function collect(): array
    {
        $data = parent::collect();
        $data['latitude'] = $data['latitude'] === '' ? null : $data['latitude'];
        $data['longitude'] = $data['longitude'] === '' ? null : $data['longitude'];
        $data['active'] = $data['active'] === '1' ? 1 : 0;

        return $data;
    }
}
