<?php
namespace HexaGen\Core\Testing;

use HexaGen\Core\Database\Model;

/**
 * Base factory for generating test model instances.
 *
 * Extend this class in database/factories/:
 *
 *   class UserFactory extends ModelFactory {
 *       protected string $model = User::class;
 *       public function definition(): array {
 *           return [
 *               'name'  => $this->faker->name(),
 *               'email' => $this->faker->unique()->safeEmail(),
 *           ];
 *       }
 *   }
 *
 * Usage in tests:
 *   User::factory()->make()
 *   User::factory()->count(5)->create()
 *   User::factory()->state(['role' => 'admin'])->create()
 */
abstract class ModelFactory
{
    protected string $model;
    protected \Faker\Generator $faker;

    private int   $count     = 1;
    private array $states    = [];
    private array $afterMake = [];

    public function __construct()
    {
        $this->faker = \Faker\Factory::create();
    }

    abstract public function definition(): array;

    // ── Fluent API ────────────────────────────────────────────────────────────

    public function count(int $n): static
    {
        $clone        = clone $this;
        $clone->count = $n;
        return $clone;
    }

    public function state(array $overrides): static
    {
        $clone           = clone $this;
        $clone->states[] = $overrides;
        return $clone;
    }

    public function afterMaking(\Closure $callback): static
    {
        $clone               = clone $this;
        $clone->afterMake[]  = $callback;
        return $clone;
    }

    // ── Output ────────────────────────────────────────────────────────────────

    public function make(): Model|array
    {
        $results = array_map(fn() => $this->buildOne(), range(1, $this->count));
        return $this->count === 1 ? $results[0] : $results;
    }

    public function create(): Model|array
    {
        $results = [];
        foreach (range(1, $this->count) as $_) {
            $model = $this->buildOne();
            $model->save();
            $results[] = $model;
        }
        return $this->count === 1 ? $results[0] : $results;
    }

    public function raw(): array|array[]
    {
        $results = array_map(fn() => $this->buildRaw(), range(1, $this->count));
        return $this->count === 1 ? $results[0] : $results;
    }

    // ── Internal ──────────────────────────────────────────────────────────────

    private function buildRaw(): array
    {
        $data = $this->definition();
        foreach ($this->states as $state) {
            $data = array_merge($data, $state instanceof \Closure ? ($state)($data) : $state);
        }
        // Resolve closures in definition values
        foreach ($data as $key => $value) {
            $data[$key] = $value instanceof \Closure ? $value() : $value;
        }
        return $data;
    }

    private function buildOne(): Model
    {
        $data  = $this->buildRaw();
        $class = $this->model;
        $model = new $class();
        foreach ($data as $key => $value) {
            $model->$key = $value;
        }
        foreach ($this->afterMake as $cb) {
            $cb($model);
        }
        return $model;
    }
}
