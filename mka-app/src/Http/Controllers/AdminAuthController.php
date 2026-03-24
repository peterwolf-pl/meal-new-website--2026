<?php

declare(strict_types=1);

namespace App\Http\Controllers;

final class AdminAuthController extends Controller
{
    public function loginForm(array $errors = []): string
    {
        if ($this->app->auth()->check()) {
            return $this->redirect('/admin');
        }

        return $this->render('admin/login', [
            'pageTitle' => 'Logowanie do CMS',
            'errors' => $errors,
        ], 'admin');
    }

    public function login(): string
    {
        if (!$this->validateCsrf()) {
            return '';
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $errors = [];

        if ($email === '') {
            $errors['email'] = 'Podaj adres e-mail.';
        }

        if ($password === '') {
            $errors['password'] = 'Podaj hasło.';
        }

        if ($errors === [] && !$this->app->auth()->attempt($email, $password)) {
            $errors['credentials'] = 'Nieprawidłowy login lub hasło.';
        }

        if ($errors !== []) {
            return $this->loginForm($errors);
        }

        $this->app->flash('success', 'Zalogowano do panelu CMS.');

        return $this->redirect('/admin');
    }

    public function logout(): string
    {
        if (!$this->validateCsrf()) {
            return '';
        }

        $this->app->auth()->logout();
        $this->app->flash('success', 'Wylogowano z panelu.');

        return $this->redirect('/admin/login');
    }
}
