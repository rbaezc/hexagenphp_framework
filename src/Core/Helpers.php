<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use HexaGen\Core\Database\DatabaseConnection;
use HexaGen\Core\Database\QueryBuilder;
use HexaGen\Core\Validation\Validator;
use HexaGen\Core\Validation\ValidationException;

if (!function_exists('request')) {
    function request(?string $key = null, mixed $default = null): mixed
    {
        // In worker mode the Kernel stores the current request; fall back to globals otherwise.
        $request = \HexaGen\Core\CurrentRequest::get() ?? Request::createFromGlobals();

        if ($key === null) {
            return $request;
        }

        $contentType = $request->headers->get('Content-Type', '');
        $data = str_contains($contentType, 'application/json')
            ? (json_decode($request->getContent(), true) ?: [])
            : array_merge($request->query->all(), $request->request->all());

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
    function db(?string $table = null): DatabaseConnection|QueryBuilder
    {
        $kernel = \HexaGen\Core\Kernel::getInstance();
        $conn   = $kernel
            ? $kernel->getContainer()->get(DatabaseConnection::class)
            : new DatabaseConnection();

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

if (!function_exists('log')) {
    /**
     * Get the logger instance, optionally for a specific channel.
     * Usage: log()->info('mensaje', ['context' => 'value'])
     *        log('stderr')->error('crítico')
     */
    function log(string $channel = 'default'): \HexaGen\Core\Log\Logger
    {
        return new \HexaGen\Core\Log\Logger($channel);
    }
}

if (!function_exists('cache')) {
    /**
     * Get the cache manager or get/set a value directly.
     * Usage: cache()->get('key')
     *        cache('key', 'default')          → get
     *        cache(['key' => 'value'], 300)    → set with TTL
     */
    function cache(string|array|null $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return \HexaGen\Core\Cache\CacheManager::driver();
        }
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                \HexaGen\Core\Cache\CacheManager::set($k, $v, is_int($default) ? $default : null);
            }
            return true;
        }
        return \HexaGen\Core\Cache\CacheManager::get($key, $default);
    }
}

if (!function_exists('DB')) {
    function DB(): \HexaGen\Core\Database\DatabaseConnection
    {
        $kernel = \HexaGen\Core\Kernel::getInstance();
        return $kernel
            ? $kernel->getContainer()->get(\HexaGen\Core\Database\DatabaseConnection::class)
            : new \HexaGen\Core\Database\DatabaseConnection();
    }
}

if (!function_exists('bcrypt')) {
    /** Genera un hash seguro de una contraseña usando bcrypt. */
    function bcrypt(string $value): string
    {
        return \HexaGen\Core\Security\Hash::make($value);
    }
}

if (!function_exists('hash_check')) {
    /** Verifica si una contraseña en texto plano coincide con un hash. */
    function hash_check(string $value, string $hash): bool
    {
        return \HexaGen\Core\Security\Hash::check($value, $hash);
    }
}

if (!function_exists('schedule')) {
    /** Registra una tarea programada. */
    function schedule(callable $callback): \HexaGen\Core\Console\ScheduledTask
    {
        return \HexaGen\Core\Console\Scheduler::call($callback);
    }
}

if (!function_exists('class_uses_recursive')) {
    /**
     * Returns all traits used by a class, its subclasses and trait hierarchies.
     * Reimplemented here since it's a Laravel helper, not a PHP built-in.
     */
    function class_uses_recursive(string|object $class): array
    {
        if (is_object($class)) {
            $class = get_class($class);
        }
        $traits = [];
        do {
            $traits = array_merge(class_uses($class, false), $traits);
        } while ($class = get_parent_class($class));

        foreach (array_keys($traits) as $trait) {
            $traits = array_merge(class_uses($trait, false), $traits);
        }

        return array_unique($traits);
    }
}

if (!function_exists('event')) {
    /**
     * Despacha un evento a todos sus listeners registrados.
     * event(new UserRegistered($user));
     */
    function event(\HexaGen\Core\Events\Event $event): \HexaGen\Core\Events\Event
    {
        return \HexaGen\Core\Events\EventDispatcher::dispatch($event);
    }
}

if (!function_exists('dispatch')) {
    /**
     * Despacha un job a la cola configurada.
     * dispatch(new EnviarEmailJob($user));
     * dispatch(new EnviarEmailJob($user))->onQueue('emails'); // encadenando
     */
    function dispatch(\HexaGen\Core\Queue\Job $job): \HexaGen\Core\Queue\Job
    {
        \HexaGen\Core\Queue\QueueManager::push($job);
        return $job;
    }
}

if (!function_exists('auth')) {
    /**
     * Access the AuthManager or a specific guard.
     * auth()->user()            → usuario autenticado
     * auth()->check()           → bool
     * auth()->attempt($e, $p)   → login por credenciales
     * auth('jwt')->user($req)   → guard JWT
     */
    function auth(?string $guard = null): \HexaGen\Core\Auth\AuthManager|\HexaGen\Core\Auth\Guards\SessionGuard|\HexaGen\Core\Auth\Guards\JwtGuard
    {
        if ($guard !== null) {
            return \HexaGen\Core\Auth\AuthManager::guard($guard);
        }
        return new class {
            public function user(): ?\HexaGen\Core\Auth\Authenticatable  { return \HexaGen\Core\Auth\AuthManager::user(); }
            public function check(): bool                                  { return \HexaGen\Core\Auth\AuthManager::check(); }
            public function guest(): bool                                  { return \HexaGen\Core\Auth\AuthManager::guest(); }
            public function id(): int|string|null                          { return \HexaGen\Core\Auth\AuthManager::id(); }
            public function attempt(string $email, string $password): bool { return \HexaGen\Core\Auth\AuthManager::attempt($email, $password); }
            public function login(\HexaGen\Core\Auth\Authenticatable $u): void { \HexaGen\Core\Auth\AuthManager::login($u); }
            public function logout(): void                                 { \HexaGen\Core\Auth\AuthManager::logout(); }
        };
    }
}

if (!function_exists('can')) {
    /** Verifica si el usuario autenticado tiene un permiso o policy. */
    function can(string $ability, mixed ...$args): bool
    {
        return \HexaGen\Core\Auth\Gate::allows($ability, ...$args);
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Return the current CSRF token for the session, generating one if needed.
     */
    function csrf_token(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Return a hidden HTML input with the CSRF token, ready to paste in a form.
     */
    function csrf_field(): string
    {
        $name  = \HexaGen\Core\Config::get('csrf.token_name', '_csrf_token');
        $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
        return "<input type=\"hidden\" name=\"{$name}\" value=\"{$token}\">";
    }
}

if (!function_exists('config')) {
    /**
     * Read a value from any config file using dot notation.
     * Example: config('cors.allowed_origins')
     */
    function config(string $key, mixed $default = null): mixed
    {
        return \HexaGen\Core\Config::get($key, $default);
    }
}

if (!function_exists('view')) {
    function view(string $template, array $data = []): Response
    {
        $kernel = \HexaGen\Core\Kernel::getInstance();
        $engine = $kernel
            ? $kernel->getContainer()->get(\HexaGen\Core\Template\TemplateEngine::class)
            : new \HexaGen\Core\Template\TemplateEngine();
        $html = $engine->render($template, $data);
        return new Response($html, 200, ['Content-Type' => 'text/html']);
    }
}

if (!function_exists('__')) {
    function __(string $key, array $replace = [], ?string $locale = null): string
    {
        return \HexaGen\Core\I18n\Translator::trans($key, $replace, $locale);
    }
}

if (!function_exists('trans')) {
    function trans(string $key, array $replace = [], ?string $locale = null): string
    {
        return \HexaGen\Core\I18n\Translator::trans($key, $replace, $locale);
    }
}

if (!function_exists('trans_choice')) {
    function trans_choice(string $key, int $count, array $replace = [], ?string $locale = null): string
    {
        return \HexaGen\Core\I18n\Translator::transChoice($key, $count, $replace, $locale);
    }
}

if (!function_exists('abort')) {
    function abort(int $code, string $message = ''): never
    {
        $messages = [
            400 => 'Bad Request', 401 => 'Unauthorized', 403 => 'Forbidden',
            404 => 'Not Found',   405 => 'Method Not Allowed',
            422 => 'Unprocessable Entity', 429 => 'Too Many Requests',
            500 => 'Internal Server Error',
        ];
        $body = $message ?: ($messages[$code] ?? 'Error');
        $response = new Response($body, $code);
        $response->send();
        exit;
    }
}

if (!function_exists('abort_if')) {
    function abort_if(bool $condition, int $code, string $message = ''): void
    {
        if ($condition) abort($code, $message);
    }
}

if (!function_exists('abort_unless')) {
    function abort_unless(bool $condition, int $code, string $message = ''): void
    {
        if (!$condition) abort($code, $message);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url, int $status = 302): Response
    {
        return new \Symfony\Component\HttpFoundation\RedirectResponse($url, $status);
    }
}

if (!function_exists('back')) {
    function back(int $status = 302): Response
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        return redirect($referer, $status);
    }
}

if (!function_exists('collect')) {
    function collect(array $items = []): \HexaGen\Core\Support\Collection
    {
        return new \HexaGen\Core\Support\Collection($items);
    }
}

if (!function_exists('str')) {
    function str(string $value): \HexaGen\Core\Support\Stringable
    {
        return \HexaGen\Core\Support\Str::of($value);
    }
}

if (!function_exists('now')) {
    function now(?string $timezone = null): \HexaGen\Core\Support\Date
    {
        return \HexaGen\Core\Support\Date::now($timezone);
    }
}

if (!function_exists('blank')) {
    function blank(mixed $value): bool
    {
        if (is_null($value)) return true;
        if (is_string($value)) return trim($value) === '';
        if (is_array($value)) return empty($value);
        return false;
    }
}

if (!function_exists('filled')) {
    function filled(mixed $value): bool
    {
        return !blank($value);
    }
}

if (!function_exists('value')) {
    function value(mixed $value, mixed ...$args): mixed
    {
        return $value instanceof \Closure ? $value(...$args) : $value;
    }
}

if (!function_exists('rescue')) {
    function rescue(\Closure $callback, mixed $rescue = null, bool $report = false): mixed
    {
        try {
            return $callback();
        } catch (\Throwable $e) {
            if ($report) {
                $handler = new \HexaGen\Core\Exceptions\Handler();
                $handler->report($e);
            }
            return value($rescue, $e);
        }
    }
}

if (!function_exists('tap')) {
    function tap(mixed $value, ?callable $callback = null): mixed
    {
        if ($callback) {
            $callback($value);
        }
        return $value;
    }
}

if (!function_exists('with')) {
    function with(mixed $value, ?callable $callback = null): mixed
    {
        return $callback ? $callback($value) : $value;
    }
}

if (!function_exists('throw_if')) {
    function throw_if(bool $condition, \Throwable|string $exception, string $message = ''): void
    {
        if ($condition) {
            throw is_string($exception) ? new $exception($message) : $exception;
        }
    }
}

if (!function_exists('throw_unless')) {
    function throw_unless(bool $condition, \Throwable|string $exception, string $message = ''): void
    {
        throw_if(!$condition, $exception, $message);
    }
}

if (!function_exists('url')) {
    function url(string $path = '', array $params = []): string
    {
        $base = rtrim((string)\HexaGen\Core\Config::get('app.url', ''), '/');
        $path = '/' . ltrim($path, '/');
        if (!empty($params)) {
            $path .= '?' . http_build_query($params);
        }
        return $base . $path;
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return \HexaGen\Core\Support\AssetManager::asset($path);
    }
}

if (!function_exists('vite')) {
    function vite(string|array $entrypoints): string
    {
        return \HexaGen\Core\Support\AssetManager::vite($entrypoints);
    }
}

if (!function_exists('route')) {
    function route(string $name, array $params = []): string
    {
        return \HexaGen\Core\Routing\Route::url($name, $params);
    }
}

if (!function_exists('notify')) {
    function notify(mixed $notifiable, \HexaGen\Core\Notifications\Notification $notification): void
    {
        \HexaGen\Core\Notifications\NotificationSender::send($notifiable, $notification);
    }
}

if (!function_exists('pipeline')) {
    function pipeline(mixed $payload): \HexaGen\Core\Pipeline\Pipeline
    {
        return (new \HexaGen\Core\Pipeline\Pipeline())->send($payload);
    }
}
