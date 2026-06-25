<?php
namespace HexaGen\Core;

abstract class ServiceProvider
{
    /**
     * When true, the provider is NOT instantiated at boot.
     * It is only loaded when one of provides() is resolved from the container.
     */
    public bool $deferred = false;

    public function __construct(protected Kernel $kernel) {}

    /**
     * Phase 1 — runs before the DI container is compiled.
     * Register services, bindings, and aliases here.
     * Never resolve services from the container in this method.
     */
    public function register(): void {}

    /**
     * Phase 2 — runs after the DI container is compiled.
     * Safe to resolve services, register event listeners, routes, etc.
     */
    public function boot(): void {}

    /**
     * For deferred providers: list the service IDs this provider supplies.
     * The provider is only booted when one of these is requested.
     */
    public function provides(): array
    {
        return [];
    }

    /**
     * Global middleware classes this provider contributes to the pipeline.
     */
    public function middlewares(): array
    {
        return [];
    }

    /**
     * Console command classes (must extend Symfony\Component\Console\Command\Command).
     */
    public function commands(): array
    {
        return [];
    }
}
