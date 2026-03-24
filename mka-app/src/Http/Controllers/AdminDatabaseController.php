<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Throwable;

final class AdminDatabaseController extends Controller
{
    public function index(): string
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        return $this->render('admin/database', [
            'pageTitle' => 'Baza danych',
            'status' => $this->app->migrator()->status(),
        ], 'admin');
    }

    public function migrate(): string
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        if (!$this->validateCsrf()) {
            return '';
        }

        try {
            $executed = $this->app->migrator()->migrate();

            if ($executed === []) {
                $this->app->flash('success', 'Baza danych jest już aktualna.');
            } else {
                $this->app->flash('success', 'Wykonano migracje: ' . implode(', ', $executed));
            }
        } catch (Throwable $exception) {
            $this->app->flash('error', 'Aktualizacja bazy nie powiodła się: ' . $exception->getMessage());
        }

        return $this->redirect('/admin/database');
    }
}
