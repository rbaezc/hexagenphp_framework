<?php
namespace HexaGen\Core\View;

use Symfony\Component\HttpFoundation\Response;

class View
{
    public static function share(string $key, mixed $value): void
    {
        ViewComposerRegistry::share($key, $value);
    }

    public static function composer(string|array $templates, callable $callback): void
    {
        ViewComposerRegistry::composer($templates, $callback);
    }

    public static function make(string $template, array $data = []): Response
    {
        // Merge shared data (lowest priority)
        $merged = array_merge(ViewComposerRegistry::getShared(), $data);

        // Apply composers for this template
        ViewComposerRegistry::applyComposers($template, $merged);

        $kernel = \HexaGen\Core\Kernel::getInstance();
        if ($kernel) {
            $engine = $kernel->getContainer()->get(\HexaGen\Core\Template\TemplateEngine::class);
        } else {
            $engine = new \HexaGen\Core\Template\TemplateEngine();
        }

        $html = $engine->render($template, $merged);
        return new Response($html, 200, ['Content-Type' => 'text/html']);
    }

    public static function exists(string $template): bool
    {
        $kernel = \HexaGen\Core\Kernel::getInstance();
        if ($kernel) {
            try {
                $engine = $kernel->getContainer()->get(\HexaGen\Core\Template\TemplateEngine::class);
                return $engine->exists($template);
            } catch (\Throwable) {}
        }
        return false;
    }
}
