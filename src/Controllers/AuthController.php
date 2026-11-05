<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Authenticator;

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (current_user() !== null) {
            $this->redirect('/dashboard');
        }

        $this->view('auth/login');
    }

    public function login(): void
    {
        $this->requireCsrf();

        $email = trim((string) $this->input('email', ''));
        $password = (string) $this->input('password', '');

        $result = (new Authenticator())->attempt($email, $password);

        if (!$result['ok']) {
            $this->flash('error', $result['error']);
            $this->rememberOld(['email' => $email]);
            $this->redirect('/login');
        }

        $this->forgetOld();
        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        $this->requireCsrf();
        (new Authenticator())->logout();
        $this->redirect('/login');
    }
}
