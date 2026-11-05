<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\HiveRepository;
use App\Repositories\InspectionRepository;
use App\Support\AuditLog;
use App\Support\HiveStatus;
use App\Validation\Validator;
use App\View\View;

final class InspectionController extends Controller
{
    public function index(): void
    {
        $this->view('inspections/index', $this->listData());
    }

    public function search(): void
    {
        echo View::renderBare('inspections/_table', $this->listData());
        exit;
    }

    private function listData(): array
    {
        $page = max(1, (int) $this->input('page', 1));
        $perPage = (int) config('app.per_page');

        $result = (new InspectionRepository())->paginateWithHive([
            'q' => $this->input('q', ''),
            'hive_id' => $this->input('hive_id', ''),
            'sort' => $this->input('sort'),
            'dir' => $this->input('dir'),
        ], $page, $perPage);

        return $result + ['q' => $this->input('q', '')];
    }

    public function create(): void
    {
        $this->view('inspections/form', [
            'item' => null,
            'errors' => [],
            'hives' => (new InspectionRepository())->inspectableHives(),
            'preselectedHiveId' => (int) $this->input('hive_id', 0),
            'today' => date('Y-m-d'),
        ]);
    }

    public function store(): void
    {
        $this->requireCsrf();
        $data = $this->collect();
        $data['inspector_user_id'] = $this->currentUserId();

        $errors = $this->validate($data);
        if ($errors !== []) {
            $this->rememberOld($data);
            $this->view('inspections/form', [
                'item' => $data, 'errors' => $errors,
                'hives' => (new InspectionRepository())->inspectableHives(),
                'preselectedHiveId' => (int) ($data['hive_id'] ?? 0),
                'today' => date('Y-m-d'),
            ]);
            return;
        }

        $id = (new InspectionRepository())->create($data);
        AuditLog::record('create', 'inspection', $id, "Inspection logged for hive #{$data['hive_id']} on {$data['inspected_on']}.");
        $this->forgetOld();
        $this->flash('success', 'Inspection logged.');
        $this->redirect('/inspections');
    }

    public function edit(array $params): void
    {
        $item = (new InspectionRepository())->find((int) $params['id']);
        if ($item === null) {
            (new ErrorController())->notFound();
            return;
        }

        $this->view('inspections/form', [
            'item' => $item,
            'errors' => [],
            'hives' => (new InspectionRepository())->inspectableHives(),
            'preselectedHiveId' => (int) $item['hive_id'],
            'today' => date('Y-m-d'),
            'editing' => true,
        ]);
    }

    public function update(array $params): void
    {
        $this->requireCsrf();
        $id = (int) $params['id'];
        $repo = new InspectionRepository();
        $item = $repo->find($id);
        if ($item === null) {
            (new ErrorController())->notFound();
            return;
        }

        $data = $this->collect();
        $data['inspector_user_id'] = (int) $this->input('inspector_user_id', $item['inspector_user_id']);

        $errors = $this->validate($data);
        if ($errors !== []) {
            $this->rememberOld($data);
            $this->view('inspections/form', [
                'item' => $item + $data, 'errors' => $errors,
                'hives' => (new InspectionRepository())->inspectableHives(),
                'preselectedHiveId' => (int) $data['hive_id'], 'today' => date('Y-m-d'), 'editing' => true,
            ]);
            return;
        }

        $repo->update($id, $data);
        AuditLog::record('update', 'inspection', $id, "Inspection #{$id} updated.");
        $this->forgetOld();
        $this->flash('success', 'Inspection updated.');
        $this->redirect('/inspections');
    }

    public function destroy(array $params): void
    {
        $this->requireCsrf();
        $id = (int) $params['id'];
        $repo = new InspectionRepository();
        $item = $repo->find($id);
        if ($item === null) {
            (new ErrorController())->notFound();
            return;
        }

        $repo->delete($id);
        AuditLog::record('delete', 'inspection', $id, "Inspection #{$id} deleted.");
        $this->flash('success', 'Inspection deleted.');
        $this->redirect('/inspections');
    }

    private function collect(): array
    {
        return [
            'hive_id' => (int) $this->input('hive_id'),
            'inspected_on' => (string) $this->input('inspected_on', date('Y-m-d')),
            'frames_of_bees' => (int) $this->input('frames_of_bees', 0),
            'brood_frames' => (int) $this->input('brood_frames', 0),
            'queen_seen' => $this->input('queen_seen') === '1' ? 1 : 0,
            'eggs_seen' => $this->input('eggs_seen') === '1' ? 1 : 0,
            'temperament' => (int) $this->input('temperament', 3),
            'honey_stores' => (int) $this->input('honey_stores', 3),
            'mites_per_300' => $this->input('mites_per_300') !== '' && $this->input('mites_per_300') !== null
                ? (int) $this->input('mites_per_300') : null,
            'disease_notes' => $this->input('disease_notes') ?: null,
            'actions_taken' => $this->input('actions_taken') ?: null,
        ];
    }

    /** @return array<string,string> */
    private function validate(array $data): array
    {
        $validator = new Validator($data, [
            'hive_id' => 'required|integer',
            'inspected_on' => 'required|date',
            'frames_of_bees' => 'required|integer|min_value:0|max_value:40',
            'brood_frames' => 'required|integer|min_value:0|max_value:40',
            'temperament' => 'required|integer|between:1,5',
            'honey_stores' => 'required|integer|between:1,5',
            'mites_per_300' => 'nullable|integer|min_value:0|max_value:300',
        ]);
        $errors = $validator->errors();

        if (!isset($errors['hive_id'])) {
            $hive = (new HiveRepository())->find((int) $data['hive_id']);
            if ($hive === null) {
                $errors['hive_id'] = 'Choose a valid hive.';
            } elseif (!HiveStatus::canReceiveActivity($hive['status'])) {
                $errors['hive_id'] = "This hive is marked \"{$hive['status']}\" and can't receive new inspections.";
            }
        }

        return $errors;
    }
}
