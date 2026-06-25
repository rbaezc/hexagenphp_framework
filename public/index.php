<?php
// public/index.php

require_once __DIR__ . '/../vendor/autoload.php';

// 1. Inicializas el núcleo de tu Framework UNA SOLA VEZ
$kernel = new HexaGen\Core\Kernel();
$kernel->boot();

// 2. Definir la lógica de procesamiento de la petición
$handler = static function () use ($kernel) {
    // FrankenPHP te da la petición actual de forma nativa
    $request = Symfony\Component\HttpFoundation\Request::createFromGlobals();
    
    $response = $kernel->handle($request);
    
    $response->send();
    
    // Limpieza rápida post-petición
    $kernel->terminate($request, $response);
};

if (function_exists('frankenphp_handle_request')) {
    // 3. Este es el bucle mágico que mantiene a tu framework vivo en memoria
    $maxRequests = 1000; // Reiniciar cada 1000 peticiones para evitar fugas de memoria
    $nbRequests = 0;
    
    // frankenphp_handle_request recibe la función handler y la ejecuta por cada petición
    while (\frankenphp_handle_request($handler)) {
        $nbRequests++;
        
        // Liberar ciclos de memoria para evitar fugas
        gc_collect_cycles();
        
        if ($nbRequests >= $maxRequests) {
            break;
        }
    }
} else {
    // Fallback de desarrollo para servidores tradicionales o CLI
    $handler();
}
