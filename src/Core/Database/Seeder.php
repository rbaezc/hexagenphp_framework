<?php
namespace HexaGen\Core\Database;

abstract class Seeder
{
    abstract public function run(): void;

    public function call(string|array $seeders): void
    {
        foreach ((array) $seeders as $seederClass) {
            echo "  Seeding: {$seederClass}\n";
            $seeder = new $seederClass();
            $seeder->run();
            echo "  Seeded:  {$seederClass}\n";
        }
    }

    protected function command(string $message): void
    {
        echo $message . "\n";
    }
}
