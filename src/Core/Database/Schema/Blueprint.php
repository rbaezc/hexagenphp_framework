<?php
namespace HexaGen\Core\Database\Schema;

class Blueprint
{
    private string $table;
    private bool   $creating;
    /** @var ColumnDefinition[] */
    private array $columns = [];
    private array $indexes = [];
    private array $drops   = [];

    public function __construct(string $table, bool $creating = true)
    {
        $this->table    = $table;
        $this->creating = $creating;
    }

    // ── Column types ──────────────────────────────────────────────────────────

    public function id(string $name = 'id'): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'id');
        $col->autoIncrement = true;
        $col->primary       = true;
        $col->unsigned      = true;
        $this->columns[]    = $col;
        return $col;
    }

    public function string(string $name, int $length = 255): ColumnDefinition
    {
        $col         = new ColumnDefinition($name, 'string');
        $col->length = $length;
        $this->columns[] = $col;
        return $col;
    }

    public function char(string $name, int $length = 1): ColumnDefinition
    {
        $col         = new ColumnDefinition($name, 'char');
        $col->length = $length;
        $this->columns[] = $col;
        return $col;
    }

    public function text(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'text');
    }

    public function longText(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'longText');
    }

    public function integer(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'integer');
    }

    public function tinyInteger(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'tinyInteger');
    }

    public function smallInteger(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'smallInteger');
    }

    public function bigInteger(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'bigInteger');
    }

    public function unsignedInteger(string $name): ColumnDefinition
    {
        $col           = $this->addColumn($name, 'integer');
        $col->unsigned = true;
        return $col;
    }

    public function unsignedBigInteger(string $name): ColumnDefinition
    {
        $col           = $this->addColumn($name, 'bigInteger');
        $col->unsigned = true;
        return $col;
    }

    public function boolean(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'boolean');
    }

    public function decimal(string $name, int $total = 8, int $places = 2): ColumnDefinition
    {
        $col         = new ColumnDefinition($name, 'decimal');
        $col->total  = $total;
        $col->places = $places;
        $this->columns[] = $col;
        return $col;
    }

    public function float(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'float');
    }

    public function double(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'double');
    }

    public function date(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'date');
    }

    public function dateTime(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'dateTime');
    }

    public function timestamp(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'timestamp');
    }

    public function json(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'json');
    }

    public function uuid(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'uuid');
    }

    public function enum(string $name, array $allowed): ColumnDefinition
    {
        $col          = new ColumnDefinition($name, 'enum');
        $col->allowed = $allowed;
        $this->columns[] = $col;
        return $col;
    }

    // ── Convenience shortcuts ─────────────────────────────────────────────────

    /** Adds created_at and updated_at nullable datetime columns. */
    public function timestamps(): void
    {
        $this->dateTime('created_at')->nullable()->default(null);
        $this->dateTime('updated_at')->nullable()->default(null);
    }

    /** Adds deleted_at nullable datetime column for soft deletes. */
    public function softDeletes(): void
    {
        $this->dateTime('deleted_at')->nullable()->default(null);
    }

    /** Unsigned big integer used as a foreign key (adds an index automatically). */
    public function foreignId(string $name): ColumnDefinition
    {
        $col           = $this->addColumn($name, 'bigInteger');
        $col->unsigned = true;
        $col->index    = true;
        return $col;
    }

    // ── Indexes ───────────────────────────────────────────────────────────────

    public function unique(string|array $columns, ?string $name = null): void
    {
        $this->indexes[] = ['type' => 'unique', 'columns' => (array) $columns, 'name' => $name];
    }

    public function index(string|array $columns, ?string $name = null): void
    {
        $this->indexes[] = ['type' => 'index', 'columns' => (array) $columns, 'name' => $name];
    }

    public function primary(string|array $columns): void
    {
        $this->indexes[] = ['type' => 'primary', 'columns' => (array) $columns, 'name' => null];
    }

    // ── Drops ─────────────────────────────────────────────────────────────────

    public function dropColumn(string|array $columns): void
    {
        $this->drops = array_merge($this->drops, (array) $columns);
    }

    // ── SQL generation ────────────────────────────────────────────────────────

    /** Returns an array of SQL statements to execute for this blueprint. */
    public function toSql(string $driver): array
    {
        return $this->creating
            ? $this->buildCreateSql($driver)
            : $this->buildAlterSql($driver);
    }

    private function buildCreateSql(string $driver): array
    {
        $colDefs    = [];
        $statements = [];

        foreach ($this->columns as $col) {
            $colDefs[] = $this->columnToSql($col, $driver);
        }

        $body = implode(",\n    ", $colDefs);
        $statements[] = "CREATE TABLE `{$this->table}` (\n    {$body}\n)";

        // Indexes from column definitions
        foreach ($this->columns as $col) {
            if ($col->unique) {
                $n = "uniq_{$this->table}_{$col->name}";
                $statements[] = "CREATE UNIQUE INDEX `{$n}` ON `{$this->table}` (`{$col->name}`)";
            } elseif ($col->index) {
                $n = "idx_{$this->table}_{$col->name}";
                $statements[] = "CREATE INDEX `{$n}` ON `{$this->table}` (`{$col->name}`)";
            }
        }

        // Explicit index declarations
        foreach ($this->indexes as $idx) {
            $statements = array_merge($statements, $this->indexToSql($idx));
        }

        return $statements;
    }

    private function buildAlterSql(string $driver): array
    {
        $statements = [];

        foreach ($this->columns as $col) {
            $statements[] = "ALTER TABLE `{$this->table}` ADD COLUMN " . $this->columnToSql($col, $driver);
        }

        foreach ($this->drops as $col) {
            $statements[] = "ALTER TABLE `{$this->table}` DROP COLUMN `{$col}`";
        }

        foreach ($this->indexes as $idx) {
            $statements = array_merge($statements, $this->indexToSql($idx));
        }

        return $statements;
    }

    private function columnToSql(ColumnDefinition $col, string $driver): string
    {
        // id columns get their own full definition to avoid driver quirks
        if ($col->type === 'id') {
            return match ($driver) {
                'pgsql'  => "`{$col->name}` BIGSERIAL PRIMARY KEY",
                'sqlite' => "`{$col->name}` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT",
                default  => "`{$col->name}` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY",
            };
        }

        $sql = "`{$col->name}` " . $this->mapType($col, $driver);

        if ($col->unsigned && !in_array($driver, ['sqlite', 'pgsql'])) {
            $sql .= ' UNSIGNED';
        }

        if ($col->primary) {
            $sql .= ' PRIMARY KEY';
            if ($col->autoIncrement && $driver === 'mysql') {
                $sql .= ' AUTO_INCREMENT';
            }
        }

        $sql .= $col->nullable ? ' NULL' : ' NOT NULL';

        if ($col->hasDefault) {
            $sql .= ' DEFAULT ' . $this->quoteDefault($col->default);
        }

        if ($col->comment && in_array($driver, ['mysql', 'mariadb'])) {
            $sql .= " COMMENT '" . addslashes($col->comment) . "'";
        }

        return $sql;
    }

    private function mapType(ColumnDefinition $col, string $driver): string
    {
        return match ($col->type) {
            'string'       => "VARCHAR({$col->length})",
            'char'         => "CHAR({$col->length})",
            'text'         => 'TEXT',
            'longText'     => match ($driver) { 'mysql', 'mariadb' => 'LONGTEXT', default => 'TEXT' },
            'integer'      => match ($driver) { 'sqlite' => 'INTEGER', default => 'INT' },
            'tinyInteger'  => match ($driver) { 'pgsql' => 'SMALLINT', 'sqlite' => 'INTEGER', default => 'TINYINT' },
            'smallInteger' => match ($driver) { 'sqlite' => 'INTEGER', default => 'SMALLINT' },
            'bigInteger'   => match ($driver) { 'sqlite' => 'INTEGER', default => 'BIGINT' },
            'boolean'      => match ($driver) { 'pgsql' => 'BOOLEAN', 'sqlite' => 'INTEGER', default => 'TINYINT(1)' },
            'decimal'      => "DECIMAL({$col->total},{$col->places})",
            'float'        => match ($driver) { 'pgsql' => 'REAL', default => 'FLOAT' },
            'double'       => match ($driver) { 'pgsql' => 'DOUBLE PRECISION', 'sqlite' => 'REAL', default => 'DOUBLE' },
            'date'         => 'DATE',
            'dateTime'     => match ($driver) { 'pgsql' => 'TIMESTAMP', default => 'DATETIME' },
            'timestamp'    => 'TIMESTAMP',
            'json'         => match ($driver) { 'pgsql' => 'JSONB', 'sqlite' => 'TEXT', default => 'JSON' },
            'uuid'         => match ($driver) { 'pgsql' => 'UUID', default => 'CHAR(36)' },
            'enum'         => !empty($col->allowed)
                                ? "ENUM('" . implode("','", array_map('addslashes', $col->allowed)) . "')"
                                : 'VARCHAR(255)',
            default        => 'TEXT',
        };
    }

    private function quoteDefault(mixed $value): string
    {
        if ($value === null)      return 'NULL';
        if (is_bool($value))     return $value ? '1' : '0';
        if (is_int($value) || is_float($value)) return (string) $value;
        return "'" . addslashes((string) $value) . "'";
    }

    private function indexToSql(array $idx): array
    {
        $cols = '`' . implode('`, `', $idx['columns']) . '`';
        $prefix = $idx['type'] === 'unique' ? 'uniq' : 'idx';
        $name  = $idx['name'] ?? "{$prefix}_{$this->table}_" . implode('_', $idx['columns']);

        return match ($idx['type']) {
            'unique'  => ["CREATE UNIQUE INDEX `{$name}` ON `{$this->table}` ({$cols})"],
            'index'   => ["CREATE INDEX `{$name}` ON `{$this->table}` ({$cols})"],
            default   => [],
        };
    }

    // ── Internal ──────────────────────────────────────────────────────────────

    private function addColumn(string $name, string $type): ColumnDefinition
    {
        $col = new ColumnDefinition($name, $type);
        $this->columns[] = $col;
        return $col;
    }
}
