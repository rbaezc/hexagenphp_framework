<?php
namespace HexaGen\Core\Testing;

use HexaGen\Core\Cache\CacheManager;
use HexaGen\Core\Events\EventDispatcher;
use HexaGen\Core\Kernel;

/**
 * Clase base para tests de integración del framework.
 * Extiéndela en tus tests y usa $this->get(), $this->post(), etc.
 *
 * Ejemplo:
 *   class ProductosTest extends TestCase {
 *       public function test_lista_productos(): void {
 *           $this->get('/productos')
 *                ->assertOk()
 *                ->assertJsonCount(3, 'data');
 *       }
 *   }
 *
 * Compatibilidad: PHPUnit 10+. Añade al composer.json:
 *   "phpunit/phpunit": "^10.0"
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected Kernel $kernel;
    protected HttpTestClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        // Use in-memory SQLite so tests don't touch the real DB
        putenv('DB_DSN=sqlite::memory:');
        putenv('CACHE_DRIVER=array');

        $this->kernel = new Kernel();
        $this->bootKernel($this->kernel);
        $this->kernel->boot();

        $this->client = new HttpTestClient($this->kernel);

        CacheManager::flush();
        EventDispatcher::flush();
    }

    protected function tearDown(): void
    {
        \HexaGen\Core\Auth\AuthManager::reset();
        CacheManager::flush();
        EventDispatcher::flush();
        parent::tearDown();
    }

    /** Override to register providers or middlewares before boot. */
    protected function bootKernel(Kernel $kernel): void {}

    // ── HTTP helpers ──────────────────────────────────────────────────────

    protected function get(string $uri, array $query = []): TestResponse
    {
        return $this->client->get($uri, $query);
    }

    protected function post(string $uri, array $body = []): TestResponse
    {
        return $this->client->post($uri, $body);
    }

    protected function postJson(string $uri, array $data = []): TestResponse
    {
        return $this->client->postJson($uri, $data);
    }

    protected function put(string $uri, array $body = []): TestResponse
    {
        return $this->client->put($uri, $body);
    }

    protected function patch(string $uri, array $body = []): TestResponse
    {
        return $this->client->patch($uri, $body);
    }

    protected function delete(string $uri): TestResponse
    {
        return $this->client->delete($uri);
    }

    protected function withHeaders(array $headers): HttpTestClient
    {
        return $this->client->withHeaders($headers);
    }

    // ── Event helpers ────────────────────────────────────────────────────

    protected function expectsEvent(string $eventClass): void
    {
        $fired = false;
        EventDispatcher::listen($eventClass, function() use (&$fired) {
            $fired = true;
        });
        $this->addToAssertionCount(1);
        register_shutdown_function(function() use (&$fired, $eventClass) {
            assert($fired, "Expected event $eventClass to be fired.");
        });
    }
}
