<?php
namespace HexaGen\Core\Database;

use PDO;

#[\AllowDynamicProperties]
abstract class Model
{
    use Traits\HasCasts;
    use Traits\HasScopes;

    protected static string $table      = '';
    protected static string $primaryKey = 'id';
    protected static array  $fillable   = [];
    protected static array  $guarded    = ['id'];

    private static ?DatabaseConnection $dbConnection = null;

    // ── Connection ────────────────────────────────────────────────────────────

    public static function setConnection(DatabaseConnection $connection): void
    {
        self::$dbConnection = $connection;
    }

    private static function getConnection(): DatabaseConnection
    {
        if (self::$dbConnection === null) {
            self::$dbConnection = new DatabaseConnection();
        }
        return self::$dbConnection;
    }

    protected static function getPdo(): PDO
    {
        return self::getConnection()->getPdo();
    }

    // ── Table name ────────────────────────────────────────────────────────────

    public static function getTableName(): string
    {
        if (static::$table !== '') {
            return static::$table;
        }
        $parts     = explode('\\', static::class);
        $className = end($parts);
        $snake     = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
        // Basic pluralisation: words ending in s/x/z/ch/sh get 'es', else 's'
        if (preg_match('/(s|x|z|ch|sh)$/i', $snake)) {
            return $snake . 'es';
        }
        return $snake . 's';
    }

    public function getRouteKeyName(): string
    {
        return static::$primaryKey;
    }

    // ── Query builder ─────────────────────────────────────────────────────────

    public static function query(): QueryBuilder
    {
        $qb = new QueryBuilder(self::getPdo(), static::getTableName(), static::class);
        if (in_array(Traits\SoftDeletes::class, class_uses_recursive(static::class), true)) {
            $qb->whereNull(static::softDeleteColumn());
        }
        return $qb;
    }

    public static function withTrashed(): QueryBuilder
    {
        return new QueryBuilder(self::getPdo(), static::getTableName(), static::class);
    }

    public static function onlyTrashed(): QueryBuilder
    {
        $col = method_exists(static::class, 'softDeleteColumn')
            ? static::softDeleteColumn() : 'deleted_at';
        return (new QueryBuilder(self::getPdo(), static::getTableName(), static::class))
            ->whereNotNull($col);
    }

    // ── Static finders ────────────────────────────────────────────────────────

    public static function all(): array
    {
        return static::query()->get();
    }

    public static function find(mixed $id): ?static
    {
        return static::query()->where(static::$primaryKey, $id)->first();
    }

    public static function findOrFail(mixed $id): static
    {
        $model = static::find($id);
        if ($model === null) {
            throw new ModelNotFoundException('No record found in [' . static::getTableName() . "] with id={$id}.");
        }
        return $model;
    }

    public static function where(string $column, mixed $value, string $operator = '='): QueryBuilder
    {
        return static::query()->where($column, $value, $operator);
    }

    // ── Static write helpers ──────────────────────────────────────────────────

    public static function create(array $attributes): static
    {
        $model = new static();
        $model->fill($attributes);
        $model->save();
        return $model;
    }

    public static function firstOrCreate(array $attributes, array $values = []): static
    {
        $qb = static::query();
        foreach ($attributes as $col => $val) {
            $qb->where($col, $val);
        }
        $existing = $qb->first();
        if ($existing !== null) {
            return $existing;
        }
        return static::create(array_merge($attributes, $values));
    }

    public static function updateOrCreate(array $attributes, array $values = []): static
    {
        $qb = static::query();
        foreach ($attributes as $col => $val) {
            $qb->where($col, $val);
        }
        $existing = $qb->first();
        if ($existing !== null) {
            $existing->fill($values)->save();
            return $existing;
        }
        return static::create(array_merge($attributes, $values));
    }

    // ── Instance write helpers ────────────────────────────────────────────────

    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if (!empty(static::$fillable) && !in_array($key, static::$fillable, true)) {
                continue;
            }
            if (in_array($key, static::$guarded, true)) {
                continue;
            }
            $this->$key = $value;
        }
        return $this;
    }

    public function update(array $attributes): bool
    {
        $this->fill($attributes);
        return $this->save();
    }

    public function fresh(): static
    {
        $pk = static::$primaryKey;
        return static::find($this->$pk);
    }

    public function refresh(): static
    {
        $fresh = $this->fresh();
        foreach (get_object_vars($fresh) as $key => $value) {
            $this->$key = $value;
        }
        return $this;
    }

    public function save(): bool
    {
        $primaryKey = static::$primaryKey;
        $pdo        = static::getPdo();
        $isNew      = !isset($this->$primaryKey) || $this->$primaryKey === null;

        if (method_exists($this, 'touchTimestamps')) {
            $this->touchTimestamps(creating: $isNew);
        }

        if (method_exists($this, 'fireObservers')) {
            $this->fireObservers('saving');
            $this->fireObservers($isNew ? 'creating' : 'updating');
        }

        $data = [];
        foreach (get_object_vars($this) as $key => $val) {
            if ($key === $primaryKey && $val === null) continue;
            $data[$key] = $val;
        }

        $qb = new QueryBuilder($pdo, static::getTableName());

        if (!$isNew) {
            unset($data[$primaryKey]);
            $rows = $qb->where($primaryKey, $this->$primaryKey)->update($data);
            if ($rows >= 0 && method_exists($this, 'fireObservers')) {
                $this->fireObservers('updated');
                $this->fireObservers('saved');
            }
            return $rows >= 0;
        }

        $success = $qb->insert($data);
        if ($success) {
            $this->$primaryKey = (int) $pdo->lastInsertId();
            if (method_exists($this, 'fireObservers')) {
                $this->fireObservers('created');
                $this->fireObservers('saved');
            }
        }
        return $success;
    }

    public function delete(): bool
    {
        $primaryKey = static::$primaryKey;
        if (!isset($this->$primaryKey) || $this->$primaryKey === null) {
            return false;
        }

        if (method_exists($this, 'fireObservers')) {
            $this->fireObservers('deleting');
        }

        if (method_exists($this, 'softDelete')) {
            $result = $this->softDelete();
        } else {
            $rows   = (new QueryBuilder(static::getPdo(), static::getTableName()))
                ->where($primaryKey, $this->$primaryKey)
                ->delete();
            $result = $rows > 0;
        }

        if ($result && method_exists($this, 'fireObservers')) {
            $this->fireObservers('deleted');
        }

        return $result;
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    protected function hasOne(string $related, string $foreignKey, string $localKey = 'id'): Relations\HasOne
    {
        return new Relations\HasOne($this, $related, $foreignKey, $localKey);
    }

    protected function hasMany(string $related, string $foreignKey, string $localKey = 'id'): Relations\HasMany
    {
        return new Relations\HasMany($this, $related, $foreignKey, $localKey);
    }

    protected function belongsTo(string $related, string $foreignKey, string $localKey = 'id'): Relations\BelongsTo
    {
        return new Relations\BelongsTo($this, $related, $foreignKey, $localKey);
    }

    protected function belongsToMany(string $related, string $pivot, string $foreignKey, string $relatedKey): Relations\BelongsToMany
    {
        return new Relations\BelongsToMany($this, $related, $pivot, $foreignKey, $relatedKey);
    }

    protected function hasManyThrough(string $related, string $through, string $firstKey, string $secondKey, string $localKey = 'id', string $secondLocalKey = 'id'): Relations\HasManyThrough
    {
        return new Relations\HasManyThrough($this, $related, $through, $firstKey, $secondKey, $localKey, $secondLocalKey);
    }

    protected function hasOneThrough(string $related, string $through, string $firstKey, string $secondKey, string $localKey = 'id', string $secondLocalKey = 'id'): Relations\HasOneThrough
    {
        return new Relations\HasOneThrough($this, $related, $through, $firstKey, $secondKey, $localKey, $secondLocalKey);
    }

    protected function morphTo(string $morphName, string $localKey = 'id'): Relations\MorphTo
    {
        return new Relations\MorphTo($this, $morphName . '_type', $morphName . '_id', $localKey);
    }

    protected function morphOne(string $related, string $morphName, string $localKey = 'id'): Relations\MorphOne
    {
        return new Relations\MorphOne($this, $related, $morphName, $localKey);
    }

    protected function morphMany(string $related, string $morphName, string $localKey = 'id'): Relations\MorphMany
    {
        return new Relations\MorphMany($this, $related, $morphName, $localKey);
    }

    // ── Hydration ─────────────────────────────────────────────────────────────

    public static function hydrate(array $data): static
    {
        $model = new static();
        foreach ($data as $key => $val) {
            $model->$key = $val;
        }
        return $model;
    }

    // ── Array / JSON ──────────────────────────────────────────────────────────

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }
}
