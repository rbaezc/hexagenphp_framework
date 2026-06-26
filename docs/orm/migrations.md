# Migrations

Migrations use a fluent **Schema Builder** — no raw SQL needed. The same migration runs on SQLite, MySQL, and PostgreSQL.

## Creating a migration

```bash
php hexaphp make:migration create_flights_table
php hexaphp make:migration add_status_to_flights_table
```

## Schema Builder

```php
// database/migrations/2024_01_01_000000_create_flights_table.php
use HexaGen\Core\Database\Migration;
use HexaGen\Core\Database\Schema\Schema;

return new class extends Migration
{
    public function up(Schema $schema): void
    {
        $schema->create('flights', function ($table) {
            $table->id();
            $table->string('origin', 3);
            $table->string('destination', 3);
            $table->decimal('price', 10, 2);
            $table->enum('class', ['economy', 'business', 'first'])->default('economy');
            $table->foreignId('airline_id')->index();
            $table->boolean('active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(Schema $schema): void
    {
        $schema->dropIfExists('flights');
    }
};
```

## Column types

| Method | SQL type |
|---|---|
| `id()` | `BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY` |
| `string('name', 255)` | `VARCHAR(255)` |
| `char('code', 3)` | `CHAR(3)` |
| `text('body')` | `TEXT` |
| `longText('content')` | `LONGTEXT` |
| `integer('qty')` | `INT` |
| `bigInteger('views')` | `BIGINT` |
| `tinyInteger('rating')` | `TINYINT` |
| `boolean('active')` | `TINYINT(1)` |
| `decimal('price', 10, 2)` | `DECIMAL(10,2)` |
| `float('lat')` | `FLOAT` |
| `double('lng')` | `DOUBLE` |
| `date('birth_date')` | `DATE` |
| `dateTime('published_at')` | `DATETIME` |
| `timestamp('logged_at')` | `TIMESTAMP` |
| `json('meta')` | `JSON` |
| `uuid('external_id')` | `CHAR(36)` |
| `enum('status', [...])` | `ENUM(...)` |
| `foreignId('user_id')` | `BIGINT UNSIGNED` + index |

## Column modifiers

```php
$table->string('email')->unique();
$table->string('nickname')->nullable();
$table->integer('score')->default(0);
$table->string('slug')->index();
$table->integer('views')->unsigned();
$table->string('name')->comment('User display name');
```

## Convenience methods

```php
$table->timestamps();   // created_at + updated_at (nullable datetime)
$table->softDeletes();  // deleted_at (nullable datetime)
$table->foreignId('user_id'); // unsigned bigint + index
```

## Indexes

```php
$table->unique('email');
$table->unique(['origin', 'destination', 'date'], 'idx_route_date');
$table->index(['origin', 'class']);
```

## Modifying tables

```php
$schema->table('flights', function ($table) {
    $table->string('status')->nullable()->default('pending');
    $table->dropColumn('old_field');
});
```

## Introspection

```php
$schema->hasTable('flights');
$schema->hasColumn('flights', 'status');
$schema->getColumns('flights');
$schema->rename('flights_old', 'flights');
```

## Running migrations

```bash
php hexaphp migrate               # run pending migrations
php hexaphp migrate:status        # show ran / pending
php hexaphp migrate:rollback      # rollback last batch
php hexaphp migrate:rollback --step=3
php hexaphp migrate:fresh         # drop all + re-run
php hexaphp migrate:fresh --seed  # + run seeders
```

Migrations are tracked by **batch** — rollback undoes the last batch, not just the last file.

## Auto-create database

The framework creates the database automatically on first connection:

- **SQLite** — creates the `.sqlite` file and any missing parent directories
- **MySQL** — runs `CREATE DATABASE IF NOT EXISTS`
- **PostgreSQL** — checks `pg_database` and creates if missing
