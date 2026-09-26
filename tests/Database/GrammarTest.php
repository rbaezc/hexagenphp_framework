<?php
namespace Tests\Database;

use HexaGen\Core\Database\Grammar;
use PHPUnit\Framework\TestCase;

final class GrammarTest extends TestCase
{
    public function test_cita_segun_el_motor(): void
    {
        $this->assertSame('`users`', Grammar::wrap('mysql', 'users'));
        $this->assertSame('"users"', Grammar::wrap('pgsql', 'users'));
        $this->assertSame('"users"', Grammar::wrap('sqlite', 'users'));
    }

    public function test_cita_tabla_y_columna_y_respeta_el_asterisco(): void
    {
        $this->assertSame('"u"."email"', Grammar::wrap('pgsql', 'u.email'));
        $this->assertSame('"u".*', Grammar::wrap('pgsql', 'u.*'));
        $this->assertSame('*', Grammar::wrap('mysql', '*'));
    }

    public function test_elimina_comillas_incrustadas(): void
    {
        $this->assertSame('"name; drop table x"', Grammar::wrap('pgsql', 'name"; drop table x'));
        $this->assertSame('`name`', Grammar::wrap('mysql', 'na`me`'));
    }

    public function test_id_autoincremental_por_motor(): void
    {
        $this->assertSame('"id" BIGSERIAL PRIMARY KEY', Grammar::autoIncrementId('pgsql'));
        $this->assertSame('"id" INTEGER PRIMARY KEY AUTOINCREMENT', Grammar::autoIncrementId('sqlite'));
        $this->assertStringContainsString('AUTO_INCREMENT', Grammar::autoIncrementId('mysql'));
    }

    public function test_insert_que_ignora_duplicados(): void
    {
        $this->assertStringEndsWith('ON CONFLICT DO NOTHING', Grammar::insertIgnore('pgsql', 'tags', ['a', 'b']));
        $this->assertStringStartsWith('INSERT OR IGNORE', Grammar::insertIgnore('sqlite', 'tags', ['a', 'b']));
        $this->assertStringStartsWith('INSERT IGNORE', Grammar::insertIgnore('mysql', 'tags', ['a', 'b']));
    }
}
