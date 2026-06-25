<?php
namespace HexaGen\Core\Testing;

use HexaGen\Core\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP test client — envía requests al Kernel sin levantar un servidor real.
 * Úsalo dentro de TestCase para testear controladores end-to-end.
 */
class HttpTestClient
{
    private array $defaultHeaders = [];
    private ?string $csrfToken    = null;

    public function __construct(private Kernel $kernel) {}

    public function withHeaders(array $headers): static
    {
        $this->defaultHeaders = array_merge($this->defaultHeaders, $headers);
        return $this;
    }

    public function withCsrfToken(string $token): static
    {
        $this->csrfToken = $token;
        return $this;
    }

    public function get(string $uri, array $query = []): TestResponse
    {
        return $this->send('GET', $uri, [], $query);
    }

    public function post(string $uri, array $body = []): TestResponse
    {
        return $this->send('POST', $uri, $body);
    }

    public function put(string $uri, array $body = []): TestResponse
    {
        return $this->send('PUT', $uri, $body);
    }

    public function patch(string $uri, array $body = []): TestResponse
    {
        return $this->send('PATCH', $uri, $body);
    }

    public function delete(string $uri): TestResponse
    {
        return $this->send('DELETE', $uri);
    }

    public function postJson(string $uri, array $data = []): TestResponse
    {
        return $this->withHeaders(['Content-Type' => 'application/json'])
                    ->send('POST', $uri, [], [], json_encode($data));
    }

    private function send(
        string $method,
        string $uri,
        array  $body    = [],
        array  $query   = [],
        string $content = ''
    ): TestResponse {
        $headers = $this->defaultHeaders;
        if ($this->csrfToken) {
            $headers['X-CSRF-Token'] = $this->csrfToken;
        }

        $server = [];
        foreach ($headers as $key => $value) {
            $server['HTTP_' . strtoupper(str_replace('-', '_', $key))] = $value;
        }

        $request = Request::create($uri, $method, $body, [], [], $server, $content ?: null);
        if ($query) {
            $request->query->add($query);
        }

        $response = $this->kernel->handle($request);
        $this->kernel->terminate($request, $response);

        return new TestResponse($response);
    }
}
