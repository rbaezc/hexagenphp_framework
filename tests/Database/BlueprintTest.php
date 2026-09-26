<?php
namespace Tests\Database;

use HexaGen\Core\Database\Schema\Blueprint;
use PHPUnit\Framework\TestCase;

final class BlueprintTest extends TestCase
{
    private function leads(): Blueprint
    {
        $table = new Blueprint('leads');
        $table->id();
        $table->string('name', 80);
        $table->boolean('active')->default(true);
        $table->enum('status', ['nuevo', 'ganado']);
        $table->foreignId('user_id');
        $table->timestamps();
        $table->index('status');
        return $table;
    }

    public function test_postgres_sin_backticks_ni_unsigned(): void
    {
        $sql = implode("\n", $this->leads()->toSql('pgsql'));

        $this->assertStringNotContainsString('`', $sql);
        $this->assertStringNotContainsString('UNSIGNED', $sql);
        $this->assertStringContainsString('CREATE TABLE "leads"', $sql);
        $this->assertStringContainsString('"id" BIGSERIAL PRIMARY KEY', $sql);
        $this->assertStringContainsString('"created_at" TIMESTAMP', $sql);
    }

    public function test_postgres_booleanos_y_enum_portables(): void
    {
        $sql = implode("\n", $this->leads()->toSql('pgsql'));

        $this->assertStringContainsString('"active" BOOLEAN NOT NULL DEFAULT TRUE', $sql);
        $this->assertStringContainsString("\"status\" VARCHAR(255) NOT NULL CHECK (\"status\" IN ('nuevo', 'ganado'))", $sql);
    }

    public function test_mysql_conserva_su_sintaxis(): void
    {
        $sql = implode("\n", $this->leads()->toSql('mysql'));

        $this->assertStringContainsString('CREATE TABLE `leads`', $sql);
        $this->assertStringContainsString("`status` ENUM('nuevo', 'ganado')", $sql);
        $this->assertStringContainsString('`user_id` BIGINT UNSIGNED', $sql);
    }

    public function test_los_indices_se_citan_por_motor(): void
    {
        $this->assertContains('CREATE INDEX "idx_leads_status" ON "leads" ("status")', $this->leads()->toSql('pgsql'));
        $this->assertContains('CREATE INDEX `idx_leads_status` ON `leads` (`status`)', $this->leads()->toSql('mysql'));
    }

    public function test_las_comillas_en_defaults_se_escapan(): void
    {
        $table = new Blueprint('notes');
        $table->string('title')->default("it's");

        $this->assertStringContainsString("DEFAULT 'it''s'", implode("\n", $table->toSql('pgsql')));
    }
}
