# Worker Mode (FrankenPHP)

The biggest performance win in HexaGen is FrankenPHP worker mode: PHP boots **once** and serves thousands of requests per process — eliminating cold-start overhead on every request.

## How it works

```php
// public/index.php
require_once __DIR__ . '/../vendor/autoload.php';

$kernel = new HexaGen\Core\Kernel();
$kernel->boot(); // runs ONCE

if (function_exists('frankenphp_handle_request')) {
    for ($i = 0; frankenphp_handle_request() && $i < 1000; ++$i) {
        $request  = Request::createFromGlobals();
        $response = $kernel->handle($request);
        $response->send();
        $kernel->terminate($request, $response);
        gc_collect_cycles();
    }
} else {
    // Traditional server fallback (Apache, Nginx, php -S)
    $request  = Request::createFromGlobals();
    $response = $kernel->handle($request);
    $response->send();
    $kernel->terminate($request, $response);
}
```

## What gets cached between requests

| Component | Cached | Safe because |
|---|---|---|
| `UrlMatcher` | ✅ Yes | Route definitions don't change at runtime |
| `ReflectionMethod` / `ReflectionClass` | ✅ Yes | Class structure is immutable |
| Middleware instances | ✅ Yes | Middlewares are stateless |
| DI Container | ✅ Yes | Bindings are immutable after boot |

## What gets reset on every request

`Kernel::terminate()` runs after every response and clears all per-request state:

```php
public function terminate(Request $request, Response $response): void
{
    AuthManager::reset();    // clears cached authenticated user
    CurrentRequest::reset(); // clears request singleton
}
```

This prevents **identity leaks** between requests — the most critical bug in worker mode apps.

## Starting the server

```bash
php hexaphp server:start
```

If FrankenPHP is not installed, the command offers to download it automatically.

## Performance comparison

| Mode | Cold start | Memory | Requests/sec |
|---|---|---|---|
| Traditional PHP-FPM | Every request | Low (per process) | ~500 |
| FrankenPHP worker | Once | Higher (shared) | ~5,000+ |

The framework is fully state-safe for worker mode — no static state leaks between requests except the intentional caches listed above.
