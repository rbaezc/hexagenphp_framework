# Configuration

Configuration files live in `config/`. Each file returns a PHP array and maps to a key you access with `Config::get()` or the `config()` helper.

## Environment variables

All sensitive or environment-specific values go in `.env`:

```ini
APP_NAME=HexaGen
APP_ENV=production        # local | production | testing
APP_DEBUG=false           # show stack traces (never true in production)
APP_URL=https://my-app.com
APP_KEY=your-32-char-random-key

DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=my_app
DB_USER=root
DB_PASSWORD=secret

CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

QUEUE_DRIVER=database

MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM=noreply@my-app.com

JWT_SECRET=your-jwt-secret
SESSION_NAME=hexagen_session

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
```

## Reading config values

```php
config('app.name');                    // "HexaGen"
config('app.debug', false);           // with default
Config::get('mail.driver');           // static access
```

## Caching config for production

```bash
php hexaphp config:cache    # compiles all config files into a single cached file
php hexaphp config:clear    # removes the cache
```

Always run `config:cache` before deploying to production.

## Available config files

| File | Key | Contents |
|---|---|---|
| `config/app.php` | `app` | Name, URL, debug, key, timezone |
| `config/auth.php` | `auth` | Guards, providers |
| `config/cache.php` | `cache` | Driver, TTL, Redis options |
| `config/cors.php` | `cors` | Allowed origins, methods, headers |
| `config/csrf.php` | `csrf` | Excluded routes |
| `config/filesystems.php` | `filesystems` | Disks (local, S3) |
| `config/logging.php` | `logging` | Channel, log path |
| `config/mail.php` | `mail` | Driver, SMTP settings |
| `config/queue.php` | `queue` | Driver, connection |
| `config/rate-limiting.php` | `rate-limiting` | Max attempts, decay |
