<?php
namespace HexaGen\Core\Database;

use PDO;

class QueryBuilder
{
    private PDO $pdo;
    private string $table;
    private array $wheres = [];
    private array $bindings = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private array $orders = [];
    private ?string $modelClass = null;
    private array $eagerLoads = [];

    public function __construct(PDO $pdo, string $table, ?string $modelClass = null)
    {
        $this->pdo = $pdo;
        $this->table = $table;
        $this->modelClass = $modelClass;
    }

    /**
     * Specify relationships to eager load.
     */
    public function with(array|string $relations): self
    {
        $this->eagerLoads = is_array($relations) ? $relations : [$relations];
        return $this;
    }

    /**
     * Add a WHERE IN constraint.
     */
    public function whereIn(string $column, array $values): self
    {
        if (empty($values)) {
            $this->wheres[] = "1 = 0";
            return $this;
        }

        $placeholders = [];
        foreach ($values as $index => $value) {
            $placeholder = ':' . str_replace('.', '_', $column) . '_in_' . $index . '_' . count($this->wheres);
            $placeholders[] = $placeholder;
            $this->bindings[$placeholder] = $value;
        }

        $this->wheres[] = "`$column` IN (" . implode(', ', $placeholders) . ")";
        return $this;
    }

    /**
     * Add a WHERE condition to the query.
     */
    public function where(string $column, mixed $value, string $operator = '='): self
    {
        $placeholder = ':' . str_replace('.', '_', $column) . '_' . count($this->wheres);
        $this->wheres[] = "$column $operator $placeholder";
        $this->bindings[$placeholder] = $value;
        return $this;
    }

    /**
     * Limit the number of records returned.
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Offset the results.
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Order the query by column.
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orders[] = "$column $direction";
        return $this;
    }

    /**
     * Execute the query and fetch all results (hydrates models if class context exists).
     */
    public function get(): array
    {
        $sql = "SELECT * FROM {$this->table}";

        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }

        if (!empty($this->orders)) {
            $sql .= " ORDER BY " . implode(', ', $this->orders);
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->bindings);
        $results = $stmt->fetchAll();

        if ($this->modelClass === null) {
            return $results;
        }

        // Hydrate rows to model objects
        $models = array_map(function ($row) {
            $class = $this->modelClass;
            return $class::hydrate($row);
        }, $results);

        // Process Eager Loading
        if (!empty($models) && !empty($this->eagerLoads)) {
            $models = $this->eagerLoadRelations($models);
        }

        return $models;
    }

    /**
     * Run eager loading resolution on relations.
     */
    private function eagerLoadRelations(array $models): array
    {
        foreach ($this->eagerLoads as $relationName) {
            $class = $this->modelClass;
            $parentInstance = new $class();
            
            if (!method_exists($parentInstance, $relationName)) {
                throw new \RuntimeException(sprintf('Relación "%s" no definida en el modelo "%s"', $relationName, $class));
            }
            
            $relation = $parentInstance->$relationName();
            if (!$relation instanceof \HexaGen\Core\Database\Relations\Relation) {
                throw new \RuntimeException(sprintf('El método de relación "%s" debe retornar una instancia de Relation.', $relationName));
            }
            
            $results = $relation->getRelationResults($models);
            $models = $relation->match($models, $results, $relationName);
        }
        
        return $models;
    }

    /**
     * Get the first result or null.
     */
    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    /**
     * Insert a new record.
     */
    public function insert(array $data): bool
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_map(fn($col) => ':' . $col, array_keys($data)));

        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        
        $bindings = [];
        foreach ($data as $key => $val) {
            $bindings[':' . $key] = $val;
        }

        return $stmt->execute($bindings);
    }

    /**
     * Update an existing record by its primary key.
     */
    public function update(mixed $id, array $data, string $primaryKey = 'id'): bool
    {
        $sets = [];
        $bindings = [':pk_val' => $id];

        foreach ($data as $key => $val) {
            $sets[] = "$key = :$key";
            $bindings[':' . $key] = $val;
        }

        $setsStr = implode(', ', $sets);
        $sql = "UPDATE {$this->table} SET $setsStr WHERE $primaryKey = :pk_val";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($bindings);
    }

    /**
     * Delete a record by primary key.
     */
    public function delete(mixed $id, string $primaryKey = 'id'): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE $primaryKey = :pk_val";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':pk_val' => $id]);
    }
}
