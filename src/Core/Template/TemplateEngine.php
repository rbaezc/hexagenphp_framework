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
        $loader = new FilesystemLoader();

        // 1. Register global views directory if it exists
        $globalViewsDir = $projectDir . '/views';
        if (is_dir($globalViewsDir)) {
            $loader->addPath($globalViewsDir);
        }

        // 2. Scan Vertical Slices and register their Views/ folders as Twig namespaces (e.g. @Vuelos)
        $slicesDir = $projectDir . '/src/Slices';
        if (is_dir($slicesDir)) {
            $dirs = glob($slicesDir . '/*', GLOB_ONLYDIR);
            foreach ($dirs as $dir) {
                $sliceName = basename($dir);
                $viewsPath = $dir . '/Views';
                if (is_dir($viewsPath)) {
                    // Registers namespace so `@Vuelos/index.twig` maps to `src/Slices/Vuelos/Views/index.twig`
                    $loader->addPath($viewsPath, $sliceName);
                }
            }
        }

        // 3. Set compilation cache options for production speed
        $cacheDir = $projectDir . '/var/cache/twig';
        $options = [
            'cache' => getenv('APP_ENV') === 'production' ? $cacheDir : false,
            'auto_reload' => true,
        ];

        $this->twig = new Environment($loader, $options);
    }

    /**
     * Render a Twig template and return the compiled HTML output string.
     */
    public function render(string $template, array $data = []): string
    {
        return $this->twig->render($template, $data);
    }

    /**
     * Retrieve raw Twig environment instance.
     */
    public function getTwig(): Environment
    {
        return $this->twig;
    }
}
