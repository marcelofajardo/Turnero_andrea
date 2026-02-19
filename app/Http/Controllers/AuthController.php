<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Infrastructure\Persistence\DatabaseConnection;
use App\Shared\Helpers\CsrfHelper;
use App\Shared\Logging\AppLogger;

/**
 * AuthController — admin login/logout.
 */
final class AuthController extends BaseController
{
    public function loginForm(): void
    {
        if (session_status() === PHP_SESSION_NONE)
            session_start();
        if (!empty($_SESSION['admin_id'])) {
            $this->redirect('/admin/dashboard');
        }
        $this->view('admin.login', ['csrf' => CsrfHelper::getToken(), 'error' => null]);
    }

    public function login(): void
    {
        if (session_status() === PHP_SESSION_NONE)
            session_start();

        $csrfToken = $_POST['_csrf'] ?? '';
        if (!CsrfHelper::validate($csrfToken)) {
            $this->view('admin.login', ['csrf' => CsrfHelper::getToken(), 'error' => 'Sesión inválida. Recargue la página.']);
            return;
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $this->view('admin.login', ['csrf' => CsrfHelper::getToken(), 'error' => 'Completá todos los campos.']);
            return;
        }

        $pdo = DatabaseConnection::getInstance();
        $stmt = $pdo->prepare('SELECT id, password_hash FROM admin_users WHERE username = :u AND is_active = 1 LIMIT 1');
        $stmt->execute([':u' => $username]);
        $row = $stmt->fetch();

        if ($row && password_verify($password, $row['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $row['id'];
            $_SESSION['admin_user'] = $username;

            $pdo->prepare('UPDATE admin_users SET last_login = NOW() WHERE id = :id')
                ->execute([':id' => $row['id']]);

            AppLogger::info("Admin login", ['username' => $username]);
            $this->redirect('/admin/dashboard');
        }

        AppLogger::warning("Failed admin login attempt", ['username' => $username, 'ip' => $_SERVER['REMOTE_ADDR']]);
        $this->view('admin.login', ['csrf' => CsrfHelper::getToken(), 'error' => 'Credenciales incorrectas.']);
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE)
            session_start();
        session_destroy();
        $this->redirect('/login');
    }
}