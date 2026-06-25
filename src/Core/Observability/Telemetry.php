<?php
namespace HexaGen\Core\Observability;

use HexaGen\Core\Config;
use HexaGen\Core\Log\Logger;

/**
 * Telemetry — tracing estructurado de operaciones.
 *
 * Crea spans para medir latencia de cualquier bloque de código:
 *
 *   $span = Telemetry::startSpan('db.query', ['sql' => 'SELECT ...']);
 *   // ... operación ...
 *   Telemetry::endSpan($span);
 *
 *   // O con wrapper automático:
 *   $result = Telemetry::trace('proceso-pago', function() {
 *       return $gateway->charge(...);
 *   }, ['amount' => 99.9]);
 *
 * Exporters:
 *   'log'  → escribe al logger estructurado (default)
 *   'otlp' → envía al OpenTelemetry Collector via HTTP/JSON
 */
class Telemetry
{
    private static string $traceId = '';
    private static array  $spans   = [];

    public static function traceId(): string
    {
        if (self::$traceId === '') {
            self::$traceId = bin2hex(random_bytes(16));
        }
        return self::$traceId;
    }

    public static function startSpan(string $name, array $attributes = []): array
    {
        return [
            'name'        => $name,
            'trace_id'    => self::traceId(),
            'span_id'     => bin2hex(random_bytes(8)),
            'start'       => microtime(true),
            'attributes'  => $attributes,
        ];
    }

    public static function endSpan(array &$span): void
    {
        $span['duration_ms'] = round((microtime(true) - $span['start']) * 1000, 2);
        self::$spans[]       = $span;
        self::export($span);
    }

    /**
     * Wrapper that automatically starts and ends a span around a callable.
     */
    public static function trace(string $name, callable $callback, array $attributes = []): mixed
    {
        if (!Config::get('telemetry.enabled', true)) {
            return $callback();
        }

        $span = self::startSpan($name, $attributes);
        try {
            $result          = $callback();
            $span['status']  = 'ok';
            return $result;
        } catch (\Throwable $e) {
            $span['status'] = 'error';
            $span['error']  = $e->getMessage();
            throw $e;
        } finally {
            self::endSpan($span);
        }
    }

    private static function export(array $span): void
    {
        $exporter = Config::get('telemetry.exporter', 'log');

        match ($exporter) {
            'otlp'  => self::exportOtlp($span),
            default => self::exportLog($span),
        };
    }

    private static function exportLog(array $span): void
    {
        (new Logger())->debug('[span] ' . $span['name'], [
            'trace_id'    => $span['trace_id'],
            'span_id'     => $span['span_id'],
            'duration_ms' => $span['duration_ms'] ?? null,
            'status'      => $span['status'] ?? 'ok',
            'attributes'  => $span['attributes'] ?? [],
        ]);
    }

    private static function exportOtlp(array $span): void
    {
        $endpoint = Config::get('telemetry.otlp.endpoint', 'http://localhost:4318/v1/traces');
        $service  = Config::get('telemetry.service', 'hexagen-app');

        $payload = json_encode([
            'resourceSpans' => [[
                'resource' => ['attributes' => [['key' => 'service.name', 'value' => ['stringValue' => $service]]]],
                'scopeSpans' => [[
                    'spans' => [[
                        'traceId'          => $span['trace_id'],
                        'spanId'           => $span['span_id'],
                        'name'             => $span['name'],
                        'startTimeUnixNano'=> (int)($span['start'] * 1e9),
                        'endTimeUnixNano'  => (int)(($span['start'] + ($span['duration_ms'] ?? 0) / 1000) * 1e9),
                        'status'           => ['code' => $span['status'] === 'error' ? 2 : 1],
                        'attributes'       => self::attributesToOtlp($span['attributes'] ?? []),
                    ]],
                ]],
            ]],
        ]);

        // Non-blocking: ignore failures (telemetry must not break the app)
        $ctx = stream_context_create(['http' => [
            'method'        => 'POST',
            'header'        => "Content-Type: application/json\r\n",
            'content'       => $payload,
            'timeout'       => 1,
            'ignore_errors' => true,
        ]]);
        @file_get_contents($endpoint, false, $ctx);
    }

    private static function attributesToOtlp(array $attrs): array
    {
        return array_map(
            fn($k, $v) => ['key' => $k, 'value' => ['stringValue' => (string)$v]],
            array_keys($attrs), $attrs
        );
    }

    /** Return all spans collected in this request (useful for debugging). */
    public static function getSpans(): array { return self::$spans; }

    /** Reset trace state between requests (called in Kernel::terminate). */
    public static function reset(): void
    {
        self::$traceId = '';
        self::$spans   = [];
    }
}
