# Changelog

## v2.1.0 — 2026-09-26

### Nuevo
- **Soporte completo de PostgreSQL.** Nueva clase `Database\Grammar` que cita identificadores según el motor (backticks en MySQL/MariaDB, comillas dobles en PostgreSQL y SQLite). La usan QueryBuilder, Schema Builder, colas, validador (`unique`/`exists`), relaciones y comandos de migración.
- `Database\MigrationsTable`: la tabla `migrations` se crea con la sintaxis de cada motor (`migrate`, `migrate:status`).
- `Bootstrap\EnvLoader`: el Kernel carga `.env` al arrancar, sin sobrescribir variables que ya existan en el entorno.
- Helper `env()` (lo usan los archivos de `config/`).
- Primera suite de pruebas del framework (`tests/`, `phpunit.xml`), con integración opcional contra PostgreSQL real (`HEXAGEN_TEST_PGSQL`).

### Corregido
- Schema Builder en PostgreSQL: `BIGSERIAL`, `TIMESTAMP`, sin `UNSIGNED`; `ENUM` se genera como `VARCHAR` + `CHECK` fuera de MySQL; defaults booleanos `TRUE`/`FALSE`; comillas en defaults escapadas.
- El QueryBuilder liga cada valor con su tipo (PostgreSQL rechazaba `false` como cadena vacía en columnas `BOOLEAN`).
- `migrate:fresh` lista y elimina tablas en PostgreSQL (`CASCADE`).
- Creación automática de la base en PostgreSQL (usaba un literal en lugar de un identificador).
- `Model::save()` solo persiste columnas (propiedades públicas declaradas); antes intentaba guardar `casts`, `hidden`, `visible`, `appends` y `timestamps`.
- `Validator`: `min`/`max` aceptan decimales (`min:0.5` se evaluaba como `min:0`).
- `LiveComponent`: `hydrateFromInput()` ignora siempre `id` y `guarded` (se podía vaciar la lista protegida desde el navegador); los atributos del contenedor se escapan; usa el `TemplateEngine` del contenedor.
- Kernel: un parámetro con tipo `FormRequest` llamado `$request` recibía el Request crudo y no se validaba.
- `Testing\TestCase`: usa `DB_DRIVER`/`DB_DATABASE` y `CacheManager::clear()` (llamaba `flush()`, que no existe).
- `Testing\TestResponse`: las aserciones usan PHPUnit; con `assert()` nativo no verificaban nada cuando `zend.assertions=-1`.
- `Testing\HttpTestClient`: `Content-Type` y `Content-Length` se envían sin prefijo `HTTP_` (el cuerpo JSON no se reconocía).
- `.env.example` documenta `DB_DRIVER`/`DB_DATABASE`, que son las variables que lee la conexión.

### Actualizar desde v2.0.x
- Cambia `DB_DSN` por `DB_DRIVER` y `DB_DATABASE` en tu `.env`.
- Si tenías tu propio cargador de `.env` o tu propio `env()`, ya no hacen falta (los del framework no sobrescriben).
