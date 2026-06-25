<?php
namespace HexaGen\Core\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Symfony\Component\HttpFoundation\Request;

abstract class AbstractController
{
    /**
     * Helper to return a JSON response.
     */
    protected function json(mixed $data, int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse($data, $status, $headers);
    }

    /**
     * Helper to return a standard HTML/text response.
     */
    protected function response(string $content, int $status = 200, array $headers = []): Response
    {
        return new Response($content, $status, $headers);
    }

    /**
     * Helper to redirect to a different URL.
     */
    protected function redirect(string $url, int $status = 302, array $headers = []): RedirectResponse
    {
        return new RedirectResponse($url, $status, $headers);
    }

    /**
     * Validate incoming request against validation rules.
     */
    protected function validate(Request $request, array $rules): array
    {
        $contentType = $request->headers->get('Content-Type');
        $data = [];
        if (str_contains((string)$contentType, 'application/json')) {
            $data = json_decode($request->getContent(), true) ?: [];
        } else {
            $data = array_merge($request->query->all(), $request->request->all());
        }

        return \validate($data, $rules);
    }
}
