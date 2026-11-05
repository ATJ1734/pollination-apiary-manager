<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\UserRepository;
use App\Support\AuditLog;
use App\Validation\Validator;
use App\View\View;

final class AdminUserController extends Controller
{
    private const ROLES = ['admin', 'manager', 'field_tech'];

    public function index(): void
    {
        $page = max(1, (int) $this->input('page', 1));
        $result = (new UserRepository())->paginate(['q' => $this->input('q', '')], $page, (int) config('app.per_page'));
        $this->view('admin/users_index', $result + ['q' => $this->input('q', '')]);
    }

    public function create(): void
    {
        $this->view('admin/users_form', ['item' => null, 'errors' => [], 'roles' => self::ROLES]);
    }

    public function store(): void
    {
        $this->requireCsrf();
        $data = $this->collect();
        $password = (string) $this->input('password', '');

        $errors = $this->validate($data, null);
        if ($password === '' || strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        }

        if ($errors !== []) {
            $this->rememberOld($data);
            $this->view('admin/users_form', ['item' => null, 'errors' => $errors, 'roles' => self::ROLES]);
            return;
        }

        $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        $data['failed_logins'] = 0;
        $data['locked_until'] = null;

        $id = (new UserRepository())->create($data);
        AuditLog::record('create', 'user', $id, "User \"{$data['name']}\" created.");
        $this->forgetOld();
        $this->flash('success', 'User created.');
        $this->redirect('/admin/users');
    }

    public function edit(array $params): void
    {
        $item = (new UserRepository())->find((int) $params['id']);
        if ($item === null) {
            (new ErrorController())->notFound();
            return;
        }

        $this->view('admin/users_form', ['item' => $item, 'errors' => [], 'roles' => self::ROLES]);
    }

    public function update(array $params): void
    {
        $this->requireCsrf();
        $id = (int) $params['id'];
        $repo = new UserRepository();
        $item = $repo->find($id);
        if ($item === null) {
            (new ErrorController())->notFound();
            return;
        }

        $data = $this->collect();
        $errors = $this->validate($data, $id);

        $password = (string) $this->input('password', '');
        if ($password !== '' && strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        }

        if ($errors !== []) {
            $this->rememberOld($data);
            $this->view('admin/users_form', ['item' => $item + $data, 'errors' => $errors, 'roles' => self::ROLES]);
            return;
        }

        if ($password !== '') {
            $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $repo->update($id, $data);
        AuditLog::record('update', 'user', $id, "User \"{$data['name']}\" updated.");
        $this->forgetOld();
        $this->flash('success', 'User updated.');
        $this->redirect('/admin/users');
    }

    public function destroy(array $params): void
    {
        $this->requireCsrf();
        $id = (int) $params['id'];

        if ($id === $this->currentUserId()) {
            $this->flash('error', "You can't delete your own account.");
            $this->redirect('/admin/users');
        }

        $repo = new UserRepository();
        $item = $repo->find($id);
        if ($item === null) {
            (new ErrorController())->notFound();
            return;
        }

        $repo->delete($id);
        AuditLog::record('delete', 'user', $id, "User \"{$item['name']}\" deleted.");
        $this->flash('success', 'User deleted.');
        $this->redirect('/admin/users');
    }

    private function collect(): array
    {
        return [
            'name' => trim((string) $this->input('name', '')),
            'email' => trim((string) $this->input('email', '')),
            'role' => (string) $this->input('role', 'field_tech'),
            'active' => $this->input('active') === '1' ? 1 : 0,
        ];
    }

    private function validate(array $data, ?int $excludeId): array
    {
        $validator = new Validator($data, [
            'name' => 'required|max:120',
            'email' => 'required|email|max:190',
            'role' => 'required|in:' . implode(',', self::ROLES),
        ]);
        $errors = $validator->errors();

        if (!isset($errors['email']) && (new UserRepository())->emailExists($data['email'], $excludeId)) {
            $errors['email'] = 'That email is already in use.';
        }

        return $errors;
    }
}
