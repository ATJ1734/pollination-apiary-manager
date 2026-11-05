<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ApiaryRepository;
use App\Repositories\HiveRepository;
use App\Support\AuditLog;
use App\Validation\Validator;
use App\View\View;

final class HiveController extends Controller
{
    private const STATUSES = ['active', 'queenless', 'dead', 'sold', 'combined'];

    public function index(): void
    {
        $this->view('hives/index', $this->listData() + ['apiaries' => (new ApiaryRepository())->allActive()]);
    }

    public function search(): void
    {
        echo View::renderBare('hives/_table', $this->listData());
        exit;
    }

    private function listData(): array
    {
        $page = max(1, (int) $this->input('page', 1));
        $perPage = (int) config('app.per_page');

        $result = (new HiveRepository())->paginateWithApiary([
            'q' => $this->input('q', ''),
            'status' => $this->input('status', ''),
            'apiary_id' => $this->input('apiary_id', ''),
            'sort' => $this->input('sort'),
            'dir' => $this->input('dir'),
        ], $page, $perPage);

        return $result + [
            'q' => $this->input('q', ''),
            'status' => $this->input('status', ''),
            'apiary_id' => $this->input('apiary_id', ''),
        ];
    }

    public function create(): void
    {
        $this->view('hives/form', [
            'item' => null,
            'errors' => [],
            'apiaries' => (new ApiaryRepository())->allActive(),
            'statuses' => self::STATUSES,
        ]);
    }

    public function store(): void
    {
        $this->requireCsrf();
        $data = $this->collect();

        $errors = $this->validate($data, null);
        if ($errors !== []) {
            $this->rememberOld($data);
            $this->view('hives/form', ['item' => null, 'errors' => $errors, 'apiaries' => (new ApiaryRepository())->allActive(), 'statuses' => self::STATUSES]);
            return;
        }

        $id = (new HiveRepository())->create($data);
        AuditLog::record('create', 'hive', $id, "Hive \"{$data['hive_code']}\" created.");
        $this->forgetOld();
        $this->flash('success', 'Hive created.');
        $this->redirect('/hives');
    }

    public function show(array $params): void
    {
        $repo = new HiveRepository();
        $hive = $repo->findWithApiary((int) $params['id']);
        if ($hive === null) {
            (new ErrorController())->notFound();
            return;
        }

        $inspections = $repo->inspectionHistory($hive['id']);

        // Oldest-to-newest for the chart; the timeline table below shows newest-first.
        $chartSource = array_reverse(array_slice($inspections, 0, 12));
        $chartLabels = array_map(static fn ($i) => $i['inspected_on'], $chartSource);
        $chartValues = array_map(
            static fn ($i) => $i['mites_per_300'] !== null ? round($i['mites_per_300'] / 3, 1) : null,
            $chartSource,
        );

        $this->view('hives/show', [
            'hive' => $hive,
            'latest' => $repo->latestInspection($hive['id']),
            'inspections' => $inspections,
            'treatments' => $repo->treatmentHistory($hive['id']),
            'placements' => $repo->placementHistory($hive['id']),
            'miteThreshold' => (float) config('app.mite_threshold'),
            'chartLabels' => $chartLabels,
            'chartValues' => $chartValues,
            'extraScripts' => ['/assets/vendor/chart.umd.min.js'],
        ]);
    }

    public function edit(array $params): void
    {
        $hive = (new HiveRepository())->find((int) $params['id']);
        if ($hive === null) {
            (new ErrorController())->notFound();
            return;
        }

        $this->view('hives/form', [
            'item' => $hive,
            'errors' => [],
            'apiaries' => (new ApiaryRepository())->allActive(),
            'statuses' => self::STATUSES,
        ]);
    }

    public function update(array $params): void
    {
        $this->requireCsrf();
        $id = (int) $params['id'];
        $repo = new HiveRepository();
        $hive = $repo->find($id);
        if ($hive === null) {
            (new ErrorController())->notFound();
            return;
        }

        $data = $this->collect();
        $errors = $this->validate($data, $id);
        if ($errors !== []) {
            $this->rememberOld($data);
            $this->view('hives/form', ['item' => $hive + $data, 'errors' => $errors, 'apiaries' => (new ApiaryRepository())->allActive(), 'statuses' => self::STATUSES]);
            return;
        }

        $repo->update($id, $data);
        AuditLog::record('update', 'hive', $id, "Hive \"{$data['hive_code']}\" updated.");
        $this->forgetOld();
        $this->flash('success', 'Hive updated.');
        $this->redirect('/hives');
    }

    public function destroy(array $params): void
    {
        $this->requireCsrf();
        $id = (int) $params['id'];
        $repo = new HiveRepository();
        $hive = $repo->find($id);
        if ($hive === null) {
            (new ErrorController())->notFound();
            return;
        }

        $repo->delete($id);
        AuditLog::record('delete', 'hive', $id, "Hive \"{$hive['hive_code']}\" deleted.");
        $this->flash('success', 'Hive deleted.');
        $this->redirect('/hives');
    }

    private function collect(): array
    {
        return [
            'hive_code' => trim((string) $this->input('hive_code', '')),
            'apiary_id' => (int) $this->input('apiary_id'),
            'status' => (string) $this->input('status', 'active'),
            'hive_type' => $this->input('hive_type') ?: null,
            'queen_installed_on' => $this->input('queen_installed_on') ?: null,
            'queen_source' => $this->input('queen_source') ?: null,
            'queen_mark_color' => $this->input('queen_mark_color') ?: null,
            'established_on' => $this->input('established_on') ?: null,
            'notes' => $this->input('notes') ?: null,
        ];
    }

    private function validate(array $data, ?int $excludeId): array
    {
        $validator = new Validator($data, [
            'hive_code' => 'required|max:20',
            'apiary_id' => 'required|integer',
            'status' => 'required|in:' . implode(',', self::STATUSES),
            'hive_type' => 'nullable|max:40',
            'queen_installed_on' => 'nullable|date',
            'queen_source' => 'nullable|max:80',
            'queen_mark_color' => 'nullable|max:30',
            'established_on' => 'nullable|date',
            'notes' => 'nullable|max:2000',
        ]);
        $errors = $validator->errors();

        if (!isset($errors['hive_code']) && (new HiveRepository())->codeExists($data['hive_code'], $excludeId)) {
            $errors['hive_code'] = 'That hive code is already in use.';
        }

        return $errors;
    }
}
