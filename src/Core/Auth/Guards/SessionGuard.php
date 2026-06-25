<?php
namespace HexaGen\Core\Auth\Guards;

use HexaGen\Core\Auth\Authenticatable;
use HexaGen\Core\Config;
use Symfony\Component\HttpFoundation\Request;

class SessionGuard
{
    private ?Authenticatable $resolved = null;

    public function attempt(string $email, string $password): bool
    {
        $user = $this->findByEmail($email);
        if (!$user || !password_verify($password, $user->getAuthPassword())) {
            return false;
        }

        // Transparently upgrade hash if cost settings changed
        if (password_needs_rehash($user->getAuthPassword(), PASSWORD_BCRYPT)) {
            $user->setAuthPassword(password_hash($password, PASSWORD_BCRYPT));
            if (method_exists($user, 'save')) {
                $user->save();
            }
        }

        $this->login($user);
        return true;
    }

    public function login(Authenticatable $user): void
    {
        $this->startSession();
        $_SESSION['_auth_id']    = $user->getAuthId();
        $_SESSION['_auth_guard'] = 'session';
        session_regenerate_id(true);
        $this->resolved = $user;
    }

    public function logout(): void
    {
        $this->startSession();
        unset($_SESSION['_auth_id'], $_SESSION['_auth_guard']);
        session_regenerate_id(true);
        $this->resolved = null;
    }

    public function user(): ?Authenticatable
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }
        $this->startSession();
        $id = $_SESSION['_auth_id'] ?? null;
        if ($id === null) {
            return null;
        }
        $this->resolved = $this->findById($id);
        return $this->resolved;
    }

    public function check(): bool { return $this->user() !== null; }
    public function guest(): bool { return !$this->check(); }
    public function id(): int|string|null { return $this->user()?->getAuthId(); }

    public function reset(): void { $this->resolved = null; }

    private function findByEmail(string $email): ?Authenticatable
    {
        $modelClass = Config::get('auth.providers.users.model');
        if (!$modelClass || !class_exists($modelClass)) {
            return null;
        }
        return $modelClass::where('email', $email)->first();
    }

    private function findById(int|string $id): ?Authenticatable
    {
        $modelClass = Config::get('auth.providers.users.model');
        if (!$modelClass || !class_exists($modelClass)) {
            return null;
        }
        return $modelClass::find($id);
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
