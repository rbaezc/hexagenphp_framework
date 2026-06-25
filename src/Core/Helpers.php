<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use HexaGen\Core\Database\DatabaseConnection;
use HexaGen\Core\Database\QueryBuilder;
use HexaGen\Core\Validation\Validator;
use HexaGen\Core\Validation\ValidationException;

if (!function_exists('request')) {
    /**
     * Get the Request object or retrieve input data.
     */
    function request(?string $key = null, mixed $default = null): mixed
    {
        // Generate request from globals (or obtain from current Kernel stack if preferred)
        $request = Request::createFromGlobals();
        if ($key === null) {
            return $request;
        }
        
        // Merged parameters (JSON body, POST body, GET query params)
        $contentType = $request->headers->get('Content-Type');
        $data = [];
        if (str_contains((string)$contentType, 'application/json')) {
            $data = json_decode($request->getContent(), true) ?: [];
        } else {
            $data = array_merge($request->query->all(), $request->request->all());
        }
        
        return $data[$key] ?? $default;
    }
}

if (!function_exists('response')) {
    /**
     * Create a standard response.
     */
    function response(string $content = '', int $status = 200, array $headers = []): Response
    {
        return new Response($content, $status, $headers);
    }
}

if (!function_exists('json')) {
    /**
     * Create a JSON response.
     */
    function json(mixed $data = [], int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse($data, $status, $headers);
    }
}

if (!function_exists('db')) {
    /**
     * Get raw DB connection or starts a query builder for a table.
     */
    function db(?string $table = null): DatabaseConnection|QueryBuilder
    {
        $conn = new DatabaseConnection();
        if ($table === null) {
            return $conn;
        }
        return new QueryBuilder($conn->getPdo(), $table);
    }
}

if (!function_exists('validate')) {
    /**
     * Validate data and throw ValidationException if errors occur.
     */
    function validate(array $data, array $rules): array
    {
        $validator = new Validator();
        if (!$validator->validate($data, $rules)) {
            throw new ValidationException($validator->getErrors());
        }
        return $data;
    }
}

if (!function_exists('view')) {
    /**
     * Render a Twig template view and return a standard Response.
     */
    function view(string $template, array $data = []): Response
    {
        $kernel = \HexaGen\Core\Kernel::getInstance();
        if ($kernel) {
            $engine = $kernel->getContainer()->get(\HexaGen\Core\Template\TemplateEngine::class);
        } else {
            $engine = new \HexaGen\Core\Template\TemplateEngine();
        }
        
        $html = $engine->render($template, $data);
        return new Response($html, 200, ['Content-Type' => 'text/html']);
    }
}
