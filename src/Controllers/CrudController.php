<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\BaseRepository;
use App\Support\AuditLog;
use App\Validation\Validator;

/**
 * List/search/create/edit/delete for a plain single-table entity with no
 * business rules of its own (apiaries, growers). Anything with real rules
 * (hives, inspections, treatments, contracts) gets its own controller.
 */
abstract class CrudController extends Controller
{
    abstract protected function repository(): BaseRepository;

    /** @return array<string,string> Validator rules */
    abstract protected function rules(): array;

    /** Lowercase singular noun used in audit log entity names and flash text. */
    abstract protected function entityName(): string;

    /** Template directory, e.g. "apiaries". */
    abstract protected function viewPrefix(): string;

    /** URL prefix, e.g. "/apiaries". */
    abstract protected function basePath(): string;

    /** Which posted fields to persist, in order. Override if a field needs transforming. */
    abstract protected function fields(): array;

    /** A short human label for the audit log summary, e.g. the apiary's name. */
    protected function label(array $data): string
    {
        return (string) ($data['name'] ?? $data['id'] ?? '');
    }

    public function index(): void
    {
        $this->view("{$this->viewPrefix()}/index", $this->listData());
    }

    public function search(): void
    {
        echo \App\View\View::renderBare("{$this->viewPrefix()}/_table", $this->listData());
        exit;
    }

    private function listData(): array
    {
        $page = max(1, (int) $this->input('page', 1));
        $perPage = (int) config('app.per_page');

        $result = $this->repository()->paginate([
            'q' => $this->input('q', ''),
            'sort' => $this->input('sort'),
            'dir' => $this->input('dir'),
        ], $page, $perPage);

        return $result + ['q' => $this->input('q', '')];
    }

    public function create(): void
    {
        $this->view("{$this->viewPrefix()}/form", ['item' => null, 'errors' => []]);
    }

    public function store(): void
    {
        $this->requireCsrf();
        $data = $this->collect();

        $validator = new Validator($data, $this->rules());
        if ($validator->fails()) {
            $this->rememberOld($data);
            $this->view("{$this->viewPrefix()}/form", ['item' => null, 'errors' => $validator->errors()]);
            return;
        }

        $id = $this->repository()->create($data);
        AuditLog::record('create', $this->entityName(), $id, ucfirst($this->entityName()) . " \"{$this->label($data)}\" created.");
        $this->forgetOld();
        $this->flash('success', ucfirst($this->entityName()) . ' created.');
        $this->redirect($this->basePath());
    }

    public function edit(array $params): void
    {
        $item = $this->repository()->find((int) $params['id']);
        if ($item === null) {
            (new ErrorController())->notFound();
            return;
        }

        $this->view("{$this->viewPrefix()}/form", ['item' => $item, 'errors' => []]);
    }

    public function update(array $params): void
    {
        $this->requireCsrf();
        $id = (int) $params['id'];
        $item = $this->repository()->find($id);
        if ($item === null) {
            (new ErrorController())->notFound();
            return;
        }

        $data = $this->collect();
        $validator = new Validator($data, $this->rules());
        if ($validator->fails()) {
            $this->rememberOld($data);
            $this->view("{$this->viewPrefix()}/form", ['item' => $item + $data, 'errors' => $validator->errors()]);
            return;
        }

        $this->repository()->update($id, $data);
        AuditLog::record('update', $this->entityName(), $id, ucfirst($this->entityName()) . " \"{$this->label($data)}\" updated.");
        $this->forgetOld();
        $this->flash('success', ucfirst($this->entityName()) . ' updated.');
        $this->redirect($this->basePath());
    }

    public function destroy(array $params): void
    {
        $this->requireCsrf();
        $id = (int) $params['id'];
        $item = $this->repository()->find($id);
        if ($item === null) {
            (new ErrorController())->notFound();
            return;
        }

        $this->repository()->delete($id);
        AuditLog::record('delete', $this->entityName(), $id, ucfirst($this->entityName()) . " \"{$this->label($item)}\" deleted.");
        $this->flash('success', ucfirst($this->entityName()) . ' deleted.');
        $this->redirect($this->basePath());
    }

    /** @return array<string,mixed> */
    protected function collect(): array
    {
        $data = [];
        foreach ($this->fields() as $field) {
            $data[$field] = $this->input($field);
        }

        return $data;
    }
}
