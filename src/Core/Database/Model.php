<?php
namespace HexaGen\Core\Database;

use PDO;

#[\AllowDynamicProperties]
abstract class Model
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';

    private static ?DatabaseConnection $dbConnection = null;

    /**
     * Set global database connection for the Models.
     */
    public static function setConnection(DatabaseConnection $connection): void
    {
        self::$dbConnection = $connection;
    }

    /**
     * Get database connection.
     */
    private static function getConnection(): DatabaseConnection
    {
        if (self::$dbConnection === null) {
            self::$dbConnection = new DatabaseConnection();
        }
        return self::$dbConnection;
    }

    /**
     * Get raw PDO instance.
     */
    protected static function getPdo(): PDO
    {
        return self::getConnection()->getPdo();
    }

    /**
     * Get resolved table name.
     */
    public static function getTableName(): string
    {
        if (static::$table !== '') {
            return static::$table;
        }

        $parts = explode('\\', static::class);
        $className = end($parts);
        $snake = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
        
        // If it ends in a consonant (except y), append 'es', otherwise append 's'
        if (preg_match('/[bcdfghjklmnpqrstvwxz]$/i', $snake)) {
            return $snake . 'es';
        }
        return $snake . 's';
    }

    /**
     * Start a new query on the model.
     */
    public static function query(): QueryBuilder
    {
        return new QueryBuilder(self::getPdo(), static::getTableName(), static::class);
    }

    /**
     * Get all records.
     */
    public static function all(): array
    {
        return static::query()->get();
    }

    /**
     * Find a record by its primary key.
     */
    public static function find(mixed $id): ?static
    {
        return static::query()->where(static::$primaryKey, $id)->first();
    }

    /**
     * Start a query with a where condition.
     */
    public static function where(string $column, mixed $value, string $operator = '='): QueryBuilder
    {
        return static::query()->where($column, $value, $operator);
    }

    /**
     * Define a belongs-to relationship.
     */
    protected function belongsTo(string $relatedClass, string $foreignKey, ?string $localKey = null): \HexaGen\Core\Database\Relations\BelongsTo
    {
        $localKey = $localKey ?: 'id';
        return new \HexaGen\Core\Database\Relations\BelongsTo($this, $relatedClass, $foreignKey, $localKey);
    }

    /**
     * Define a has-many relationship.
     */
    protected function hasMany(string $relatedClass, string $foreignKey, ?string $localKey = null): \HexaGen\Core\Database\Relations\HasMany
    {
        $localKey = $localKey ?: 'id';
        return new \HexaGen\Core\Database\Relations\HasMany($this, $relatedClass, $foreignKey, $localKey);
    }

    /**
     * Hydrate database row into a model instance.
     */
    public static function hydrate(array $data): static
    {
        $model = new static();
        foreach ($data as $key => $val) {
            $model->$key = $val;
        }
        return $model;
    }

    /**
     * Save the model to database (Insert or Update).
     */
    public function save(): bool
    {
        $primaryKey = static::$primaryKey;
        $table = static::getTableName();
        $pdo = static::getPdo();

        $properties = get_object_vars($this);
        $data = [];
        foreach ($properties as $key => $val) {
            if ($key === $primaryKey && $val === null) {
                continue;
            }
            $data[$key] = $val;
        }

        if (isset($this->$primaryKey) && $this->$primaryKey !== null) {
            // Update
            $qb = new QueryBuilder($pdo, $table);
            unset($data[$primaryKey]);
            return $qb->update($this->$primaryKey, $data, $primaryKey);
        } else {
            // Insert
            $qb = new QueryBuilder($pdo, $table);
            $success = $qb->insert($data);
            if ($success) {
                $this->$primaryKey = (int)$pdo->lastInsertId();
            }
            return $success;
        }
    }

    /**
     * Delete the model from the database.
     */
    public function delete(): bool
    {
        $primaryKey = static::$primaryKey;
        if (!isset($this->$primaryKey) || $this->$primaryKey === null) {
            return false;
        }
        
        $qb = new QueryBuilder(static::getPdo(), static::getTableName());
        return $qb->delete($this->$primaryKey, $primaryKey);
    }
}
