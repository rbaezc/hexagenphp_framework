<?php
namespace HexaGen\Core\Database;

use HexaGen\Core\Database\Schema\Schema;

abstract class Migration
{
    abstract public function up(Schema $schema): void;

    abstract public function down(Schema $schema): void;
}
