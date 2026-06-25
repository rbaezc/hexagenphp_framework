<?php
/**
 * Telemetry / Observabilidad Configuration
 *
 * Controla tracing de requests, métricas y exportación de spans.
 *
 * Exporters disponibles: 'log' (escribe al logger), 'otlp' (OpenTelemetry collector)
 * Para usar OTLP necesitas un collector corriendo (ej: Jaeger, Zipkin, Grafana Tempo).
 */
return [

    'enabled'  => filter_var(getenv('TELEMETRY_ENABLED') ?: true, FILTER_VALIDATE_BOOLEAN),

    'service'  => getenv('OTEL_SERVICE_NAME') ?: 'hexagen-app',

    'exporter' => getenv('OTEL_EXPORTER') ?: 'log',

    'otlp' => [
        'endpoint' => getenv('OTEL_EXPORTER_OTLP_ENDPOINT') ?: 'http://localhost:4318/v1/traces',
    ],

    // Registra duración, status code y route de cada request HTTP
    'trace_requests' => true,

    // Registra queries SQL con duración (cuidado con datos sensibles en producción)
    'trace_queries'  => filter_var(getenv('TELEMETRY_TRACE_QUERIES') ?: false, FILTER_VALIDATE_BOOLEAN),

];
