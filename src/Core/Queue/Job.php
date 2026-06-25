<?php
namespace HexaGen\Core\Queue;

/**
 * Clase base para todos los jobs de la aplicación.
 *
 * Uso:
 *   class EnviarEmailJob extends Job {
 *       public function __construct(private string $to, private string $body) {}
 *
 *       public function handle(): void {
 *           // lógica de envío
 *       }
 *   }
 *
 *   // Despachar:
 *   dispatch(new EnviarEmailJob('user@example.com', 'Bienvenido'));
 *   // o en una cola específica:
 *   dispatch(new EnviarEmailJob(...))->onQueue('emails');
 */
abstract class Job
{
    public int    $tries     = 3;
    public int    $timeout   = 60;
    protected string $queue = 'default';

    abstract public function handle(): void;

    public function onQueue(string $queue): static
    {
        $this->queue = $queue;
        return $this;
    }

    public function getQueue(): string
    {
        return $this->queue;
    }

    /** Called when all retry attempts have failed. Override to handle failures. */
    public function failed(\Throwable $e): void {}
}
