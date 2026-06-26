<?php
namespace HexaGen\Core\Exceptions;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use HexaGen\Core\Log\Logger;

class Handler implements ExceptionHandlerInterface
{
    protected array $dontReport = [];

    public function report(\Throwable $e): void
    {
        foreach ($this->dontReport as $type) {
            if ($e instanceof $type) {
                return;
            }
        }

        (new Logger('default'))->error($e->getMessage(), [
            'exception' => get_class($e),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
            'trace'     => $e->getTraceAsString(),
        ]);
    }

    public function render(Request $request, \Throwable $e): Response
    {
        $debug = filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN);

        if ($request->headers->get('Accept') === 'application/json'
            || str_contains($request->headers->get('Content-Type', ''), 'json')) {
            return $this->renderJson($e, $debug);
        }

        return $this->renderHtml($request, $e, $debug);
    }

    protected function renderJson(\Throwable $e, bool $debug): JsonResponse
    {
        $data = ['message' => $debug ? $e->getMessage() : 'Server Error'];
        if ($debug) {
            $data['exception'] = get_class($e);
            $data['file']      = $e->getFile();
            $data['line']      = $e->getLine();
        }

        $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
        return new JsonResponse($data, $status);
    }

    protected function renderHtml(Request $request, \Throwable $e, bool $debug): Response
    {
        $status    = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
        $viewsDir  = dirname(__DIR__, 3) . '/resources/views/errors';
        $viewFile  = "{$viewsDir}/{$status}.twig";
        $fallback  = "{$viewsDir}/500.twig";

        if (file_exists($viewFile) || file_exists($fallback)) {
            try {
                $kernel = \HexaGen\Core\Kernel::getInstance();
                if ($kernel) {
                    $engine = $kernel->getContainer()->get(\HexaGen\Core\Template\TemplateEngine::class);
                    $template = file_exists($viewFile) ? "errors/{$status}.twig" : 'errors/500.twig';
                    $html = $engine->render($template, [
                        'exception' => $e,
                        'debug'     => $debug,
                        'status'    => $status,
                    ]);
                    return new Response($html, $status, ['Content-Type' => 'text/html']);
                }
            } catch (\Throwable) {
                // Fall through to plain text
            }
        }

        $body = $debug
            ? "Error {$status}: " . $e->getMessage() . "\n" . $e->getTraceAsString()
            : "Error {$status}";

        return new Response($body, $status, ['Content-Type' => 'text/plain']);
    }
}
