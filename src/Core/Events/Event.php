<?php
namespace HexaGen\Core\Events;

/** Clase base para todos los eventos de la aplicación. */
abstract class Event
{
    public readonly float $firedAt;

    public function __construct()
    {
        $this->firedAt = microtime(true);
    }
}
