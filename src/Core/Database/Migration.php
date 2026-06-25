<?php
namespace HexaGen\Core\Database;

use PDO;

abstract class Migration
{
    /**
     * Run the migration schema updates.
     */
    abstract public function up(PDO $pdo): void;

    /**
     * Reverse the migration schema updates.
     */
    abstract public function down(PDO $pdo): void;
}
