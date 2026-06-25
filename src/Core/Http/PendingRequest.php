<?php
namespace HexaGen\Core\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use HexaGen\Core\Support\Traits\Macroable;

class PendingRequest
{
    use Macroable;

    private array $options = [];
    private array $headers = [];
    private ?string $baseUrl = null;
    private int $tries = 1;
    private int $retryDelay = 100;
    private bool $throwOnFailure = false;

    public function baseUrl(string $url): static
    {
        $this->baseUrl = rtrim($url, '/');
        return $this;
    }

    public function withHeaders(array $headers): static
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    public function withToken(string $token, string $type = 'Bearer'): static
    {
        return $this->withHeaders(['Authorization' => "$type $token"]);
    }

    public function withBasicAuth(string $username, string $password): static
    {
        $this->options['auth'] = [$username, $password];
        return $this;
    }

    public function withDigestAuth(string $username, string $password): static
    {
        $this->options['auth'] = [$username, $password, 'digest'];
        return $this;
    }

    public function asJson(): static
    {
        return $this->withHeaders(['Content-Type' => 'application/json', 'Accept' => 'application/json']);
    }

    public function asForm(): static
    {
        return $this->withHeaders(['Content-Type' => 'application/x-www-form-urlencoded']);
    }

    public function accept(string $contentType): static
    {
        return $this->withHeaders(['Accept' => $contentType]);
    }

    public function acceptJson(): static
    {
        return $this->accept('application/json');
    }

    public function timeout(int $seconds): static
    {
        $this->options['timeout'] = $seconds;
        return $this;
    }

    public function connectTimeout(int $seconds): static
    {
        $this->options['connect_timeout'] = $seconds;
        return $this;
    }

    public function retry(int $times, int $sleepMs = 100): static
    {
        $this->tries      = $times;
        $this->retryDelay = $sleepMs;
        return $this;
    }

    public function withOptions(array $options): static
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    public function withoutRedirecting(): static
    {
        $this->options['allow_redirects'] = false;
        return $this;
    }

    public function withoutVerifying(): static
    {
        $this->options['verify'] = false;
        return $this;
    }

    public function throw(): static
    {
        $this->throwOnFailure = true;
        return $this;
    }

    private function buildClient(): Client
    {
        $config = $this->options;
        if ($this->baseUrl) {
            $config['base_uri'] = $this->baseUrl . '/';
        }
        if (!empty($this->headers)) {
            $config['headers'] = $this->headers;
        }
        return new Client($config);
    }

    private function send(string $method, string $url, array $options = []): HttpResponse
    {
        $client    = $this->buildClient();
        $attempts  = 0;
        $lastError = null;

        while ($attempts < $this->tries) {
            try {
                $response     = $client->request($method, $url, $options);
                $httpResponse = new HttpResponse($response);
                if ($this->throwOnFailure) {
                    $httpResponse->throw();
                }
                return $httpResponse;
            } catch (RequestException $e) {
                $lastError = $e;
                $attempts++;
                if ($attempts < $this->tries) {
                    usleep($this->retryDelay * 1000);
                }
            }
        }

        throw $lastError ?? new \RuntimeException("HTTP request failed");
    }

    public function get(string $url, array $query = []): HttpResponse
    {
        return $this->send('GET', $url, $query ? ['query' => $query] : []);
    }

    public function post(string $url, array $data = []): HttpResponse
    {
        $contentType = $this->headers['Content-Type'] ?? '';
        $key = str_contains($contentType, 'json') ? 'json' : 'form_params';
        return $this->send('POST', $url, $data ? [$key => $data] : []);
    }

    public function postJson(string $url, array $data = []): HttpResponse
    {
        return $this->asJson()->post($url, $data);
    }

    public function put(string $url, array $data = []): HttpResponse
    {
        return $this->send('PUT', $url, $data ? ['json' => $data] : []);
    }

    public function patch(string $url, array $data = []): HttpResponse
    {
        return $this->send('PATCH', $url, $data ? ['json' => $data] : []);
    }

    public function delete(string $url, array $data = []): HttpResponse
    {
        return $this->send('DELETE', $url, $data ? ['json' => $data] : []);
    }

    public function head(string $url, array $query = []): HttpResponse
    {
        return $this->send('HEAD', $url, $query ? ['query' => $query] : []);
    }
}
