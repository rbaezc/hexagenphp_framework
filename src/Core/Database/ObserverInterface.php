<?php
namespace HexaGen\Core\Database;

/**
 * Define los hooks del ciclo de vida de un modelo.
 * Implementa solo los métodos que necesites.
 *
 * Registro:
 *   User::observe(UserObserver::class);
 */
interface ObserverInterface
{
    public function creating(Model $model): void;
    public function created(Model $model): void;
    public function updating(Model $model): void;
    public function updated(Model $model): void;
    public function saving(Model $model): void;
    public function saved(Model $model): void;
    public function deleting(Model $model): void;
    public function deleted(Model $model): void;
}
