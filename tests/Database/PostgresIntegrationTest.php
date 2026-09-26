<?php
namespace Tests\Database;

use HexaGen\Core\Database\MigrationsTable;
use HexaGen\Core\Database\QueryBuilder;
use HexaGen\Core\Database\Schema\Blueprint;
use HexaGen\Core\Database\Schema\Schema;
use PHPUnit\Framework\TestCase;

/**
 * Integración contra un PostgreSQL real. Se omite si no hay servidor configurado:
 *
 *   HEXAGEN_TEST_PGSQL="host=127.0.0.1;port=5432;dbname=hexagen_test" \
 *   HEXAGEN_TEST_PGSQL_USER=postgres HEXAGEN_TEST_PGSQL_PASSWORD=secreto vendor/bin/phpunit
 */
final class PostgresIntegrationTest extends TestCase
{
    private \PDO $pdo;

    protected function setUp(): void
    {
        $dsn = getenv('HEXAGEN_TEST_PGSQL');
        if (!$dsn) {
            $this->markTestSkipped('Define HEXAGEN_TEST_PGSQL para probar contra PostgreSQL.');
        }

        $this->pdo = new \PDO('pgsql:' . $dsn, getenv('HEXAGEN_TEST_PGSQL_USER') ?: null, getenv('HEXAGEN_TEST_PGSQL_PASSWORD') ?: null, [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]);

        $schema = new Schema($this->pdo);
        $schema->dropIfExists('hg_items');
        $schema->create('hg_items', function (Blueprint $t) {
            $t->id();
            $t->string('name', 80);
            $t->boolean('active')->default(true);
            $t->enum('kind', ['a', 'b'])->default('a');
            $t->json('meta')->nullable();
            $t->timestamps();
            $t->unique('name');
        });
    }

    protected function tearDown(): void
    {
        if (isset($this->pdo)) {
            (new Schema($this->pdo))->dropIfExists('hg_items');
        }
    }

    public function test_crud_completo_con_query_builder(): void
    {
        $qb = fn () => new QueryBuilder($this->pdo, 'hg_items');

        $id = $qb()->insertGetId(['name' => 'uno', 'active' => false, 'kind' => 'b', 'created_at' => date('Y-m-d H:i:s')]);
        $this->assertGreaterThan(0, (int) $id);

        $row = $qb()->where('id', (int) $id)->first();
        $this->assertSame('uno', $row['name']);
        $this->assertFalse((bool) $row['active']);

        $this->assertSame(1, $qb()->where('name', 'uno')->update(['active' => true]));
        $this->assertSame(1, $qb()->where('active', true)->count());
    }

    public function test_introspeccion_y_restricciones(): void
    {
        $schema = new Schema($this->pdo);

        $this->assertTrue($schema->hasTable('hg_items'));
        $this->assertTrue($schema->hasColumn('hg_items', 'meta'));

        $this->expectException(\PDOException::class); // CHECK del enum
        (new QueryBuilder($this->pdo, 'hg_items'))->insert(['name' => 'dos', 'kind' => 'z']);
    }

    public function test_tabla_de_migraciones(): void
    {
        MigrationsTable::ensure($this->pdo);
        MigrationsTable::ensure($this->pdo); // idempotente

        $this->assertTrue((new Schema($this->pdo))->hasTable('migrations'));
    }
}
