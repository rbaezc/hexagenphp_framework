<?php
namespace HexaGen\Core\Template;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class TemplateEngine
{
    private Environment $twig;

    public function __construct()
    {
        $projectDir = dirname(dirname(dirname(__DIR__)));
        $loader     = new FilesystemLoader();

        // 1. Register global views directory
        foreach (['resources/views', 'views'] as $dir) {
            $path = $projectDir . '/' . $dir;
            if (is_dir($path)) {
                $loader->addPath($path);
            }
        }

        // 2. Scan Vertical Slices and register Views/ as Twig namespaces (@SliceName)
        $slicesDir = $projectDir . '/src/Slices';
        if (is_dir($slicesDir)) {
            foreach (glob($slicesDir . '/*', GLOB_ONLYDIR) as $dir) {
                $sliceName = basename($dir);
                $viewsPath = $dir . '/Views';
                if (is_dir($viewsPath)) {
                    $loader->addPath($viewsPath, $sliceName);
                }
            }
        }

        $cacheDir = $projectDir . '/var/cache/twig';
        $this->twig = new Environment($loader, [
            'cache'       => getenv('APP_ENV') === 'production' ? $cacheDir : false,
            'auto_reload' => true,
        ]);

        $this->registerFunctions();
    }

    private function registerFunctions(): void
    {
        $this->twig->addFunction(new \Twig\TwigFunction('csrf_token', 'csrf_token'));
        $this->twig->addFunction(new \Twig\TwigFunction('csrf_field', 'csrf_field', ['is_safe' => ['html']]));
        $this->twig->addFunction(new \Twig\TwigFunction('config', 'config'));
        $this->twig->addFunction(new \Twig\TwigFunction('route', '\HexaGen\Core\Routing\Route::url'));
        $this->twig->addFunction(new \Twig\TwigFunction('asset', '\HexaGen\Core\Support\AssetManager::asset'));
        $this->twig->addFunction(new \Twig\TwigFunction('vite', '\HexaGen\Core\Support\AssetManager::vite', ['is_safe' => ['html']]));
        $this->twig->addFunction(new \Twig\TwigFunction('__', fn(string $key, array $r = []) => __($key, $r)));
        $this->twig->addFunction(new \Twig\TwigFunction('trans', fn(string $key, array $r = []) => __($key, $r)));
        $this->twig->addFunction(new \Twig\TwigFunction('auth', 'auth'));
        $this->twig->addFunction(new \Twig\TwigFunction('can', 'can'));
        $this->twig->addFunction(new \Twig\TwigFunction('app', fn() => \HexaGen\Core\Application::class));
    }

    public function render(string $template, array $data = []): string
    {
        // Merge shared view data from View::share()
        $shared = \HexaGen\Core\View\ViewComposerRegistry::getShared();
        $merged = array_merge($shared, $data);

        // Apply view composers for this template
        \HexaGen\Core\View\ViewComposerRegistry::applyComposers($template, $merged);

        return $this->twig->render($template, $merged);
    }

    public function exists(string $template): bool
    {
        return $this->twig->getLoader()->exists($template);
    }

    public function getTwig(): Environment
    {
        return $this->twig;
    }

    public function addFunction(\Twig\TwigFunction $function): void
    {
        $this->twig->addFunction($function);
    }

    public function addFilter(\Twig\TwigFilter $filter): void
    {
        $this->twig->addFilter($filter);
    }

    public function addGlobal(string $name, mixed $value): void
    {
        $this->twig->addGlobal($name, $value);
    }
}
