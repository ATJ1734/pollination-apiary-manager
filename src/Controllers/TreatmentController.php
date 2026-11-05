<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\HiveRepository;
use App\Repositories\TreatmentRepository;
use App\Support\AuditLog;
use App\Support\HiveStatus;
use App\Validation\DateRules;
use App\Validation\Validator;
use App\View\View;

final class TreatmentController extends Controller
{
    public function index(): void
    {
        $this->view('treatments/index', $this->listData());
    }

    public function search(): void
    {
        echo View::renderBare('treatments/_table', $this->listData());
        exit;
    }

    private function listData(): array
    {
        $page = max(1, (int) $this->input('page', 1));
        $perPage = (int) config('app.per_page');

        $result = (new TreatmentRepository())->paginateWithHive([
            'q' => $this->input('q', ''),
            'sort' => $this->input('sort'),
            'dir' => $this->input('dir'),
        ], $page, $perPage);

        return $result + ['q' => $this->input('q', '')];
    }

    public function create(): void
    {
        $this->view('treatments/form', [
            'item' => null,
            'errors' => [],
            'hives' => (new TreatmentRepository())->treatableHives(),
            'preselectedHiveId' => (int) $this->input('hive_id', 0),
            'today' => date('Y-m-d'),
        ]);
    }

    public function store(): void
    {
        $this->requireCsrf();
        $data = $this->collect();

        $errors = $this->validate($data);
        if ($errors !== []) {
            $this->rememberOld($data);
            $this->view('treatments/form', [
                'item' => $data, 'errors' => $errors,
                'hives' => (new TreatmentRepository())->treatableHives(),
                'preselectedHiveId' => (int) ($data['hive_id'] ?? 0), 'today' => date('Y-m-d'),
            ]);
            return;
        }

        $id = (new TreatmentRepository())->create($data);
        AuditLog::record('create', 'treatment', $id, "Treatment \"{$data['product']}\" applied to hive #{$data['hive_id']}.");
        $this->forgetOld();
        $this->flash('success', 'Treatment logged.');
        $this->redirect('/treatments');
    }

    public function edit(array $params): void
    {
        $item = (new TreatmentRepository())->find((int) $params['id']);
        if ($item === null) {
            (new ErrorController())->notFound();
            return;
        }

        $this->view('treatments/form', [
            'item' => $item, 'errors' => [],
            'hives' => (new TreatmentRepository())->treatableHives(),
            'preselectedHiveId' => (int) $item['hive_id'], 'today' => date('Y-m-d'), 'editing' => true,
        ]);
    }

    public function update(array $params): void
    {
        $this->requireCsrf();
        $id = (int) $params['id'];
        $repo = new TreatmentRepository();
        $item = $repo->find($id);
        if ($item === null) {
            (new ErrorController())->notFound();
            return;
        }

        $data = $this->collect();
        $errors = $this->validate($data);
        if ($errors !== []) {
            $this->rememberOld($data);
            $this->view('treatments/form', [
                'item' => $item + $data, 'errors' => $errors,
                'hives' => (new TreatmentRepository())->treatableHives(),
                'preselectedHiveId' => (int) $data['hive_id'], 'today' => date('Y-m-d'), 'editing' => true,
            ]);
            return;
        }

        $repo->update($id, $data);
        AuditLog::record('update', 'treatment', $id, "Treatment #{$id} updated.");
        $this->forgetOld();
        $this->flash('success', 'Treatment updated.');
        $this->redirect('/treatments');
    }

    public function destroy(array $params): void
    {
        $this->requireCsrf();
        $id = (int) $params['id'];
        $repo = new TreatmentRepository();
        $item = $repo->find($id);
        if ($item === null) {
            (new ErrorController())->notFound();
            return;
        }

        $repo->delete($id);
        AuditLog::record('delete', 'treatment', $id, "Treatment #{$id} deleted.");
        $this->flash('success', 'Treatment deleted.');
        $this->redirect('/treatments');
    }

    private function collect(): array
    {
        return [
            'hive_id' => (int) $this->input('hive_id'),
            'product' => trim((string) $this->input('product', '')),
            'applied_on' => (string) $this->input('applied_on', date('Y-m-d')),
            'dose' => $this->input('dose') ?: null,
            'remove_by' => $this->input('remove_by') ?: null,
            'removed_on' => $this->input('removed_on') ?: null,
            'notes' => $this->input('notes') ?: null,
        ];
    }

    private function validate(array $data): array
    {
        $validator = new Validator($data, [
            'hive_id' => 'required|integer',
            'product' => 'required|max:80',
            'applied_on' => 'required|date',
            'dose' => 'nullable|max:80',
            'remove_by' => 'nullable|date',
            'removed_on' => 'nullable|date',
            'notes' => 'nullable|max:2000',
        ]);
        $errors = $validator->errors();

        if (!isset($errors['hive_id'])) {
            $hive = (new HiveRepository())->find((int) $data['hive_id']);
            if ($hive === null) {
                $errors['hive_id'] = 'Choose a valid hive.';
            } elseif (!HiveStatus::canReceiveActivity($hive['status'])) {
                $errors['hive_id'] = "This hive is marked \"{$hive['status']}\" and can't receive new treatments.";
            }
        }

        if (!isset($errors['remove_by']) && !DateRules::onOrAfter($data['remove_by'], $data['applied_on'])) {
            $errors['remove_by'] = "Remove-by date can't come before the applied date.";
        }

        if (!isset($errors['removed_on']) && !DateRules::onOrAfter($data['removed_on'], $data['applied_on'])) {
            $errors['removed_on'] = "Removed date can't come before the applied date.";
        }

        return $errors;
    }
}
