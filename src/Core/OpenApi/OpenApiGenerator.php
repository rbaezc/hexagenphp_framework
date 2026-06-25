<?php
namespace HexaGen\Core\OpenApi;

use Symfony\Component\Routing\RouteCollection;

class OpenApiGenerator
{
    private array $spec = [];

    public function __construct(private RouteCollection $routes) {}

    public function generate(): array
    {
        $this->spec = [
            'openapi' => '3.0.3',
            'info'    => [
                'title'   => (string) \HexaGen\Core\Config::get('app.name', 'HexaGen API'),
                'version' => (string) \HexaGen\Core\Config::get('app.version', '1.0.0'),
            ],
            'paths'      => [],
            'components' => ['schemas' => []],
        ];

        foreach ($this->routes->all() as $name => $route) {
            if (str_starts_with($name, '_') || str_starts_with($route->getPath(), '/_')) {
                continue;
            }

            $path    = $this->convertPath($route->getPath());
            $methods = $route->getMethods() ?: ['GET'];

            foreach ($methods as $method) {
                if (strtoupper($method) === 'HEAD') {
                    continue;
                }

                $operation = $this->buildOperation($route, $name, $method);
                $this->spec['paths'][$path][strtolower($method)] = $operation;
            }
        }

        return $this->spec;
    }

    private function convertPath(string $path): string
    {
        // Convert Symfony {param} to OpenAPI {param} (already compatible)
        return $path;
    }

    private function buildOperation(
        \Symfony\Component\Routing\Route $route,
        string $name,
        string $method
    ): array {
        $controller = $route->getDefault('_controller');
        $docblock   = $this->extractDocblock($controller);
        $attributes = $this->extractPhpAttributes($controller);

        $operation = [
            'operationId' => $name,
            'summary'     => $attributes['summary'] ?? $docblock['summary'] ?? $name,
            'description' => $attributes['description'] ?? $docblock['description'] ?? '',
            'tags'        => $attributes['tags'] ?? [$this->inferTag($route->getPath())],
            'parameters'  => $this->buildParameters($route),
            'responses'   => $attributes['responses'] ?? [
                '200' => ['description' => 'OK'],
                '422' => ['description' => 'Validation Error'],
                '500' => ['description' => 'Server Error'],
            ],
        ];

        if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'], true)) {
            $operation['requestBody'] = [
                'content' => [
                    'application/json' => [
                        'schema' => ['type' => 'object'],
                    ],
                ],
            ];
        }

        return $operation;
    }

    private function buildParameters(\Symfony\Component\Routing\Route $route): array
    {
        $params = [];
        preg_match_all('/\{([^}]+)\}/', $route->getPath(), $matches);
        foreach ($matches[1] as $param) {
            $optional = str_ends_with($param, '?');
            $name     = rtrim($param, '?');
            $params[] = [
                'name'     => $name,
                'in'       => 'path',
                'required' => !$optional,
                'schema'   => ['type' => 'string'],
            ];
        }
        return $params;
    }

    private function inferTag(string $path): string
    {
        $parts = array_filter(explode('/', ltrim($path, '/')));
        $first = reset($parts) ?: 'default';
        return ucfirst(str_replace(['-', '_'], ' ', $first));
    }

    private function extractDocblock(mixed $controller): array
    {
        if (!is_array($controller) || count($controller) !== 2) {
            return [];
        }
        [$class, $method] = $controller;
        if (!is_string($class) || !class_exists($class) || !method_exists($class, $method)) {
            return [];
        }
        $reflection = new \ReflectionMethod($class, $method);
        $comment    = $reflection->getDocComment();
        if (!$comment) {
            return [];
        }
        preg_match('/\*\s+([^@\n][^\n]+)/m', $comment, $summary);
        return ['summary' => trim($summary[1] ?? '')];
    }

    private function extractPhpAttributes(mixed $controller): array
    {
        // Future: parse #[OpenApiGet], #[OpenApiPost] PHP 8 attributes
        return [];
    }

    public function toYaml(): string
    {
        // Minimal YAML serializer (no dependency on symfony/yaml)
        return $this->arrayToYaml($this->generate(), 0);
    }

    private function arrayToYaml(mixed $data, int $indent): string
    {
        if (is_null($data))    return "null\n";
        if (is_bool($data))    return ($data ? 'true' : 'false') . "\n";
        if (is_numeric($data)) return $data . "\n";
        if (is_string($data)) {
            if (str_contains($data, "\n") || str_contains($data, ':') || str_contains($data, "'")) {
                return '"' . addcslashes($data, '"\\') . '"' . "\n";
            }
            return $data . "\n";
        }

        $pad    = str_repeat('  ', $indent);
        $result = '';

        if (array_is_list($data)) {
            foreach ($data as $item) {
                $result .= $pad . '- ';
                if (is_array($item)) {
                    $result .= "\n" . $this->arrayToYaml($item, $indent + 1);
                } else {
                    $result .= $this->arrayToYaml($item, 0);
                }
            }
        } else {
            foreach ($data as $key => $value) {
                $result .= $pad . $key . ': ';
                if (is_array($value)) {
                    $result .= "\n" . $this->arrayToYaml($value, $indent + 1);
                } else {
                    $result .= $this->arrayToYaml($value, 0);
                }
            }
        }

        return $result;
    }

    public function toJson(bool $pretty = true): string
    {
        return json_encode($this->generate(), $pretty ? JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES : 0);
    }
}
