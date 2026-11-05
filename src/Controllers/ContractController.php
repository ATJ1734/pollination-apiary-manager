<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ContractRepository;
use App\Repositories\GrowerRepository;
use App\Support\AuditLog;
use App\Validation\Validator;
use App\View\View;

final class ContractController extends Controller
{
    private const STATUSES = ['draft', 'signed', 'active', 'completed', 'invoiced'];

    public function index(): void
    {
        $this->view('contracts/index', $this->listData());
    }

    public function search(): void
    {
        echo View::renderBare('contracts/_table', $this->listData());
        exit;
    }

    private function listData(): array
    {
        $page = max(1, (int) $this->input('page', 1));
        $perPage = (int) config('app.per_page');

        $result = (new ContractRepository())->paginateWithGrower([
            'q' => $this->input('q', ''),
            'status' => $this->input('status', ''),
            'sort' => $this->input('sort'),
            'dir' => $this->input('dir'),
        ], $page, $perPage);

        if (is_field_tech()) {
            foreach ($result['rows'] as &$row) {
                unset($row['rate_per_hive']);
            }
        }

        return $result + ['q' => $this->input('q', ''), 'status' => $this->input('status', '')];
    }

    public function create(): void
    {
        $this->requireRole(['admin', 'manager']);
        $this->view('contracts/form', ['item' => null, 'errors' => [], 'growers' => (new GrowerRepository())->all(), 'statuses' => self::STATUSES]);
    }

    public function store(): void
    {
        $this->requireRole(['admin', 'manager']);
        $this->requireCsrf();
        $data = $this->collect();

        $validator = new Validator($data, $this->rules());
        if ($validator->fails()) {
            $this->rememberOld($data);
            $this->view('contracts/form', ['item' => null, 'errors' => $validator->errors(), 'growers' => (new GrowerRepository())->all(), 'statuses' => self::STATUSES]);
            return;
        }
        if ($data['end_date'] < $data['start_date']) {
            $this->rememberOld($data);
            $this->view('contracts/form', ['item' => null, 'errors' => ['end_date' => "End date can't come before the start date."], 'growers' => (new GrowerRepository())->all(), 'statuses' => self::STATUSES]);
            return;
        }

        $id = (new ContractRepository())->create($data);
        AuditLog::record('create', 'contract', $id, "Contract for {$data['crop']} ({$data['season_year']}) created.");
        $this->forgetOld();
        $this->flash('success', 'Contract created.');
        $this->redirect("/contracts/{$id}");
    }

    public function show(array $params): void
    {
        $repo = new ContractRepository();
        $contract = $repo->findWithGrower((int) $params['id']);
        if ($contract === null) {
            (new ErrorController())->notFound();
            return;
        }
        if (is_field_tech()) {
            unset($contract['rate_per_hive']);
        }

        $this->view('contracts/show', [
            'contract' => $contract,
            'placements' => $repo->activePlacements($contract['id']),
            'placedCount' => $repo->placedCount($contract['id']),
        ]);
    }

    public function edit(array $params): void
    {
        $this->requireRole(['admin', 'manager']);
        $contract = (new ContractRepository())->find((int) $params['id']);
        if ($contract === null) {
            (new ErrorController())->notFound();
            return;
        }

        $this->view('contracts/form', ['item' => $contract, 'errors' => [], 'growers' => (new GrowerRepository())->all(), 'statuses' => self::STATUSES]);
    }

    public function update(array $params): void
    {
        $this->requireRole(['admin', 'manager']);
        $this->requireCsrf();
        $id = (int) $params['id'];
        $repo = new ContractRepository();
        $contract = $repo->find($id);
        if ($contract === null) {
            (new ErrorController())->notFound();
            return;
        }

        $data = $this->collect();
        $validator = new Validator($data, $this->rules());
        $errors = $validator->errors();
        if ($errors === [] && $data['end_date'] < $data['start_date']) {
            $errors['end_date'] = "End date can't come before the start date.";
        }
        if ($errors !== []) {
            $this->rememberOld($data);
            $this->view('contracts/form', ['item' => $contract + $data, 'errors' => $errors, 'growers' => (new GrowerRepository())->all(), 'statuses' => self::STATUSES]);
            return;
        }

        $repo->update($id, $data);
        AuditLog::record('update', 'contract', $id, "Contract #{$id} updated.");
        $this->forgetOld();
        $this->flash('success', 'Contract updated.');
        $this->redirect("/contracts/{$id}");
    }

    public function destroy(array $params): void
    {
        $this->requireRole(['admin', 'manager']);
        $this->requireCsrf();
        $id = (int) $params['id'];
        $repo = new ContractRepository();
        $contract = $repo->find($id);
        if ($contract === null) {
            (new ErrorController())->notFound();
            return;
        }

        $repo->delete($id);
        AuditLog::record('delete', 'contract', $id, "Contract #{$id} deleted.");
        $this->flash('success', 'Contract deleted.');
        $this->redirect('/contracts');
    }

    public function print(array $params): void
    {
        $this->requireRole(['admin', 'manager']);
        $repo = new ContractRepository();
        $contract = $repo->findWithGrower((int) $params['id']);
        if ($contract === null) {
            (new ErrorController())->notFound();
            return;
        }

        echo View::renderBare('contracts/print', [
            'contract' => $contract,
            'placements' => $repo->activePlacements($contract['id']),
            'company' => config('company'),
        ]);
        exit;
    }

    private function collect(): array
    {
        return [
            'grower_id' => (int) $this->input('grower_id'),
            'crop' => trim((string) $this->input('crop', '')),
            'season_year' => (int) $this->input('season_year'),
            'hives_required' => (int) $this->input('hives_required'),
            'min_frames_of_bees' => (int) $this->input('min_frames_of_bees'),
            'rate_per_hive' => (float) $this->input('rate_per_hive'),
            'start_date' => (string) $this->input('start_date'),
            'end_date' => (string) $this->input('end_date'),
            'status' => (string) $this->input('status', 'draft'),
        ];
    }

    private function rules(): array
    {
        return [
            'grower_id' => 'required|integer',
            'crop' => 'required|max:60',
            'season_year' => 'required|integer',
            'hives_required' => 'required|integer|min_value:1',
            'min_frames_of_bees' => 'required|integer|min_value:0',
            'rate_per_hive' => 'required|numeric|min_value:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'status' => 'required|in:' . implode(',', self::STATUSES),
        ];
    }
}
