# Installation

## Requirements

- PHP 8.3+
- Composer
- SQLite (dev) / MySQL / PostgreSQL (production)
- Redis _(optional — cache and queues)_
- FrankenPHP _(optional — recommended for production)_

## Create a new project

```bash
composer create-project rbaezc/hexagenphp-framework my-app
cd my-app
```

## Configure environment

```bash
cp .env.example .env
```

Open `.env` and set your values:

```ini
APP_NAME=MyApp
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_KEY=                        # generate a random 32-char key

DB_DRIVER=sqlite
DB_DATABASE=database            # creates database/database.sqlite
```

## Run migrations

```bash
php hexaphp migrate
```

The database file is created automatically — no manual setup needed.

## Start the server

```bash
php hexaphp server:start
```

Open [http://localhost:8000](http://localhost:8000).

## MySQL / PostgreSQL

```ini
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=my_app
DB_USER=root
DB_PASSWORD=secret
```

The framework creates the database automatically if it doesn't exist.

## Next steps

- [Architecture](/guide/architecture) — understand Vertical Slices
- [Worker Mode](/guide/worker-mode) — run with FrankenPHP
- [HexaORM Models](/orm/models) — your first model
