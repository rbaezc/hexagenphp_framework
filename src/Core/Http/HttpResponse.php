<?php
namespace HexaGen\Core\Http;

use Psr\Http\Message\ResponseInterface;

class HttpResponse
{
    public function __construct(private ResponseInterface $response) {}

    public function status(): int
    {
        return $this->response->getStatusCode();
    }

    public function body(): string
    {
        return (string) $this->response->getBody();
    }

    public function json(bool $assoc = true): mixed
    {
        return json_decode($this->body(), $assoc);
    }

    public function header(string $name): ?string
    {
        return $this->response->getHeaderLine($name) ?: null;
    }

    public function headers(): array
    {
        return $this->response->getHeaders();
    }

    public function ok(): bool           { return $this->status() >= 200 && $this->status() < 300; }
    public function created(): bool      { return $this->status() === 201; }
    public function accepted(): bool     { return $this->status() === 202; }
    public function noContent(): bool    { return $this->status() === 204; }
    public function redirect(): bool     { return $this->status() >= 300 && $this->status() < 400; }
    public function badRequest(): bool   { return $this->status() === 400; }
    public function unauthorized(): bool { return $this->status() === 401; }
    public function forbidden(): bool    { return $this->status() === 403; }
    public function notFound(): bool     { return $this->status() === 404; }
    public function unprocessable(): bool{ return $this->status() === 422; }
    public function serverError(): bool  { return $this->status() >= 500; }
    public function clientError(): bool  { return $this->status() >= 400 && $this->status() < 500; }
    public function failed(): bool       { return $this->serverError() || $this->clientError(); }
    public function successful(): bool   { return $this->ok(); }

    public function throw(): static
    {
        if ($this->failed()) {
            throw new \RuntimeException("HTTP request returned status {$this->status()}: {$this->body()}");
        }
        return $this;
    }

    public function throwIf(bool $condition): static
    {
        return $condition ? $this->throw() : $this;
    }

    public function __toString(): string { return $this->body(); }
}
