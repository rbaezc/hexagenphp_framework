# Middleware

## Built-in middleware

| Alias | Class | Description |
|---|---|---|
| `auth` | `AuthMiddleware` | Require session login |
| `auth:jwt` | `AuthMiddleware` | Require JWT token |
| `guest` | `GuestMiddleware` | Redirect if already logged in |
| `authorize:perm` | `AuthorizeMiddleware` | Require a Gate permission |
| `csrf` | `CsrfMiddleware` | CSRF token validation |
| `cors` | `CorsMiddleware` | CORS headers |
| `throttle` | `RateLimitMiddleware` | Rate limiting |
| `security` | `SecurityHeadersMiddleware` | Security headers |
| `maintenance` | `MaintenanceMiddleware` | 503 when app is down |

## Applying middleware

```php
// On a single route
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth');

// Multiple
Route::get('/admin', [AdminController::class, 'index'])
    ->middleware(['auth', 'authorize:admin']);

// On a group
Route::group(['middleware' => ['auth:jwt']], function () {
    Route::resource('/flights', FlightsController::class);
});
```

## Middleware groups

```php
// config/app.php — pre-configured groups:
'web' => ['csrf', 'session', 'security'],
'api' => ['throttle', 'cors'],
```

## Creating custom middleware

```bash
php hexaphp make:middleware LogRequestMiddleware
```

```php
namespace HexaGen\Slices\Shared\Middleware;

use HexaGen\Core\Middleware\MiddlewareInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LogRequestMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $start = microtime(true);

        $response = $next($request);

        $ms = round((microtime(true) - $start) * 1000);
        (new Logger())->info("{$request->getMethod()} {$request->getPathInfo()} {$response->getStatusCode()} {$ms}ms");

        return $response;
    }
}
```

Register it in `src/Core/Kernel.php` or in your slice's `Services.php`:

```php
protected array $middlewareAliases = [
    'log.request' => LogRequestMiddleware::class,
];
```
