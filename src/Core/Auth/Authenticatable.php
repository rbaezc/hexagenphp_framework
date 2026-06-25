<?php
namespace HexaGen\Core\Auth;

/**
 * Interface que debe implementar el modelo de usuario de la aplicación.
 */
interface Authenticatable
{
    public function getAuthId(): int|string;
    public function getAuthPassword(): string;
    public function setAuthPassword(string $hashed): void;
    public function getRoles(): array;
    public function getPermissions(): array;
}
