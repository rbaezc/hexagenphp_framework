<?php
namespace HexaGen\Core\Testing;

use Symfony\Component\HttpFoundation\Response;

/**
 * Wrapper de Response con assertions fluidas para tests.
 */
class TestResponse
{
    private ?array $decodedJson = null;

    public function __construct(private Response $response) {}

    public function assertStatus(int $status): static
    {
        \assert(
            $this->response->getStatusCode() === $status,
            "Expected status $status, got {$this->response->getStatusCode()}.\nBody: {$this->response->getContent()}"
        );
        return $this;
    }

    public function assertOk(): static      { return $this->assertStatus(200); }
    public function assertCreated(): static { return $this->assertStatus(201); }
    public function assertNoContent(): static { return $this->assertStatus(204); }
    public function assertNotFound(): static { return $this->assertStatus(404); }
    public function assertUnauthorized(): static { return $this->assertStatus(401); }
    public function assertForbidden(): static { return $this->assertStatus(403); }
    public function assertUnprocessable(): static { return $this->assertStatus(422); }

    public function assertJson(array $subset): static
    {
        $json = $this->json();
        foreach ($subset as $key => $value) {
            \assert(
                isset($json[$key]) && $json[$key] === $value,
                "JSON key '$key' expected '{$value}', got '" . ($json[$key] ?? 'null') . "'"
            );
        }
        return $this;
    }

    public function assertJsonCount(int $count, string $key = 'data'): static
    {
        $json  = $this->json();
        $items = $json[$key] ?? [];
        \assert(
            count($items) === $count,
            "Expected $count items in '$key', got " . count($items)
        );
        return $this;
    }

    public function assertJsonPath(string $path, mixed $expected): static
    {
        $value = $this->jsonPath($path);
        \assert($value === $expected, "JSON path '$path': expected '$expected', got '$value'");
        return $this;
    }

    public function assertHeader(string $name, string $expected): static
    {
        $actual = $this->response->headers->get($name);
        \assert($actual === $expected, "Header '$name': expected '$expected', got '$actual'");
        return $this;
    }

    public function json(?string $key = null): mixed
    {
        if ($this->decodedJson === null) {
            $this->decodedJson = json_decode($this->response->getContent(), true) ?? [];
        }
        return $key ? ($this->decodedJson[$key] ?? null) : $this->decodedJson;
    }

    public function status(): int    { return $this->response->getStatusCode(); }
    public function content(): string { return $this->response->getContent(); }
    public function response(): Response { return $this->response; }

    private function jsonPath(string $path): mixed
    {
        $data = $this->json();
        foreach (explode('.', $path) as $segment) {
            $data = $data[$segment] ?? null;
        }
        return $data;
    }
}
