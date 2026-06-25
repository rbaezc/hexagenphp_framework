<?php
namespace HexaGen\Core\Auth;

/**
 * RBAC Gate — define y verifica políticas de autorización.
 *
 * Registrar una política:
 *   Gate::define('editar-post', fn($user, $post) => $user->id === $post->user_id);
 *   Gate::role('admin');                // define que 'admin' tiene todos los permisos
 *
 * Verificar:
 *   Gate::allows('editar-post', $post)  → bool
 *   Gate::denies('editar-post', $post)  → bool
 *   Gate::authorize('editar-post', $post) → lanza AuthorizationException si deniega
 */
class Gate
{
    private static array $policies    = [];
    private static array $rolePerms   = [];

    public static function define(string $ability, callable $callback): void
    {
        self::$policies[$ability] = $callback;
    }

    /** Grant a role all listed abilities (or '*' for every ability). */
    public static function role(string $role, array|string $abilities = '*'): void
    {
        self::$rolePerms[$role] = $abilities;
    }

    public static function allows(string $ability, mixed ...$args): bool
    {
        $user = AuthManager::user();
        if (!$user) {
            return false;
        }

        // Role-based shortcut
        foreach ($user->getRoles() as $role) {
            $perms = self::$rolePerms[$role] ?? null;
            if ($perms === '*' || (is_array($perms) && in_array($ability, $perms, true))) {
                return true;
            }
        }

        // Permission-based shortcut (direct on user)
        if (in_array($ability, $user->getPermissions(), true)) {
            return true;
        }

        // Policy callback
        if (isset(self::$policies[$ability])) {
            return (bool)(self::$policies[$ability])($user, ...$args);
        }

        return false;
    }

    public static function denies(string $ability, mixed ...$args): bool
    {
        return !self::allows($ability, ...$args);
    }

    public static function authorize(string $ability, mixed ...$args): void
    {
        if (!self::allows($ability, ...$args)) {
            throw new AuthorizationException("No autorizado para: $ability");
        }
    }
}
