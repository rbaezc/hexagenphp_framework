<?php
namespace HexaGen\Core\Pipeline;

class Pipeline
{
    private mixed $payload  = null;
    private array $stages   = [];
    private string $method  = 'handle';

    public function send(mixed $payload): static
    {
        $this->payload = $payload;
        return $this;
    }

    public function through(array $stages): static
    {
        $this->stages = $stages;
        return $this;
    }

    public function via(string $method): static
    {
        $this->method = $method;
        return $this;
    }

    public function thenReturn(): mixed
    {
        return $this->then(fn($payload) => $payload);
    }

    public function then(callable $destination): mixed
    {
        $pipeline = array_reduce(
            array_reverse($this->stages),
            $this->carry(),
            $destination
        );

        return $pipeline($this->payload);
    }

    private function carry(): callable
    {
        return function (callable $stack, mixed $stage) {
            return function (mixed $payload) use ($stack, $stage) {
                if (is_callable($stage)) {
                    return $stage($payload, $stack);
                }

                if (is_string($stage) && class_exists($stage)) {
                    $instance = new $stage();
                    $method   = $this->method;
                    return $instance->$method($payload, $stack);
                }

                throw new \InvalidArgumentException('Invalid pipeline stage: ' . print_r($stage, true));
            };
        };
    }
}
