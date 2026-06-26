# Security

HexaGen ships with security hardened by default. Most protections require zero configuration.

## CSRF Protection

All `POST`, `PUT`, `PATCH`, `DELETE` requests on the `web` middleware group are CSRF-protected.

Token validation uses `hash_equals()` — timing-safe, immune to timing attacks.

```twig
{# In any Twig form #}
<form method="POST" action="/flights">
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
    ...
</form>
```

Exclude routes in `config/csrf.php`:
```php
return ['except' => ['/webhooks/*', '/api/*']];
```

## JWT Authentication

```ini
JWT_SECRET=your-secret-min-32-chars
```

- Algorithm locked to **HS256** — `alg:none` is blocked
- Tokens include `jti` (unique ID), `nbf`, `exp`
- Blacklist via cache — invalidated tokens are rejected immediately

```php
$token = auth('jwt')->attempt(['email' => $email, 'password' => $password]);
auth('jwt')->user();   // validates and returns user from token
```

## Session Security

- `session_regenerate_id(true)` called on every **login and logout** — prevents session fixation
- Session cookie is `HttpOnly` and `SameSite=Lax` by default

## Password Hashing

```php
Hash::make('password');           // bcrypt
Hash::check('password', $hash);  // verify
```

Passwords are automatically rehashed if the bcrypt cost factor is outdated — transparent to the user.

## SQL Injection

All QueryBuilder methods quote identifiers and use a whitelist for operators:

```php
// Safe — uses prepared statements
Flight::query()->where('origin', $userInput)->get();

// Identifiers quoted — prevents injection via column names
Flight::query()->orderBy($column)->get(); // $column is validated
```

## Security Headers

Applied automatically to every response:

| Header | Value |
|---|---|
| `X-Frame-Options` | `SAMEORIGIN` |
| `X-Content-Type-Options` | `nosniff` |
| `X-XSS-Protection` | `1; mode=block` |
| `Referrer-Policy` | `strict-origin-when-cross-origin` |
| `Strict-Transport-Security` | `max-age=31536000` (production) |

## Stack Traces

Stack traces are only shown when `APP_DEBUG=true`. In production, errors return a generic JSON response or error page — never internal details.

## Environment Files

`.env` is in `.gitignore` by default. Never commit secrets to version control.
