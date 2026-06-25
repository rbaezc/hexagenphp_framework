<?php
namespace HexaGen\Core\Security;

/**
 * Wrapper seguro de password_hash / password_verify.
 * Siempre usa bcrypt (PASSWORD_BCRYPT) con un cost configurable.
 *
 * Uso:
 *   $hash = Hash::make($plaintext);
 *   Hash::check($plaintext, $hash);   // → bool
 *   Hash::needsRehash($hash);         // → bool (para migrar hashes antiguos)
 */
class Hash
{
    private static int $cost = 12;

    public static function setCost(int $cost): void
    {
        self::$cost = max(4, min(31, $cost));
    }

    public static function make(string $value): string
    {
        return password_hash($value, PASSWORD_BCRYPT, ['cost' => self::$cost]);
    }

    public static function check(string $value, string $hashedValue): bool
    {
        if ($hashedValue === '') {
            return false;
        }
        return password_verify($value, $hashedValue);
    }

    public static function needsRehash(string $hashedValue): bool
    {
        return password_needs_rehash($hashedValue, PASSWORD_BCRYPT, ['cost' => self::$cost]);
    }
}
