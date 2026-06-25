<?php
namespace HexaGen\Core\Auth;

use HexaGen\Core\Auth\Guards\JwtGuard;
use HexaGen\Core\Auth\Guards\SessionGuard;
use HexaGen\Core\Config;
use Symfony\Component\HttpFoundation\Request;

/**
 * Facade de autenticación. Accede vía el helper auth().
 *
 * auth()->user()           → usuario autenticado (null si no hay sesión)
 * auth()->attempt($e, $p) → intenta login por sesión
 * auth()->login($user)    → login manual
 * auth()->logout()        → cerrar sesión
 * auth()->check()         → ¿está autenticado?
 * auth('jwt')->user($req) → usuario via JWT Bearer token
 */
class AuthManager
{
    private static array $guards = [];
    private static ?Request $currentRequest = null;

    public static function setRequest(Request $request): void
    {
        self::$currentRequest = $request;
    }

    public static function guard(?string $name = null): SessionGuard|JwtGuard
    {
        $name ??= Config::get('auth.default', 'session');

        if (isset(self::$guards[$name])) {
            return self::$guards[$name];
        }

        $config = Config::get("auth.guards.$name", []);
        $driver = $config['driver'] ?? 'session';

        self::$guards[$name] = match ($driver) {
            'jwt'   => new JwtGuard(),
            default => new SessionGuard(),
        };

        return self::$guards[$name];
    }

    // Convenience proxy — session guard methods
    public static function attempt(string $email, string $password): bool
    {
        return self::guard()->attempt($email, $password);
    }

    public static function login(Authenticatable $user): void
    {
        self::guard()->login($user);
    }

    public static function logout(): void
    {
        self::guard()->logout();
    }

    public static function user(): ?Authenticatable
    {
        $guard = self::guard();
        if ($guard instanceof JwtGuard) {
            return $guard->user(self::$currentRequest ?? Request::createFromGlobals());
        }
        return $guard->user();
    }

    public static function check(): bool { return self::user() !== null; }
    public static function guest(): bool { return !self::check(); }
    public static function id(): int|string|null { return self::user()?->getAuthId(); }

    /**
     * Reset per-request state. MUST be called in Kernel::terminate() so
     * FrankenPHP worker mode never leaks auth identity across requests.
     */
    public static function reset(): void
    {
        foreach (self::$guards as $guard) {
            if (method_exists($guard, 'reset')) {
                $guard->reset();
            }
        }
        self::$currentRequest = null;
    }
}
