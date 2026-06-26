<?php
namespace HexaGen\Core\Database;

use PDO;

class QueryBuilder
{
    private PDO $pdo;
    private string $table;
    private array $wheres    = [];
    private array $bindings  = [];
    private ?int $limit      = null;
    private ?int $offset     = null;
    private array $orders    = [];
    private array $selects   = [];
    private array $joins     = [];
    private array $groups    = [];
    private array $havings   = [];
    private ?string $modelClass         = null;
    private array $eagerLoads           = [];
    private array $skippedGlobalScopes  = [];
    private bool $skipAllGlobalScopes   = false;
    private bool $useOrForNext          = false;

    public function __construct(
        PDO $pdo,
        string $table,
        ?string $modelClass = null,
        bool $skipGlobalScopes = false
    ) {
        $this->pdo               = $pdo;
        $this->table             = $table;
        $this->modelClass        = $modelClass;
        $this->skipAllGlobalScopes = $skipGlobalScopes;

        if ($modelClass && !$skipGlobalScopes && method_exists($modelClass, 'getGlobalScopes')) {
            foreach ($modelClass::getGlobalScopes() as $name => $scope) {
                $scope($this);
            }
        }
    }

    // ── Column selection ──────────────────────────────────────────────────────

    public function select(array|string $columns): static
    {
        $this->selects = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    public function addSelect(array|string $columns): static
    {
        $add = is_array($columns) ? $columns : [$columns];
        $this->selects = array_merge($this->selects, $add);
        return $this;
    }

    public function selectRaw(string $expression): static
    {
        $this->selects[] = new RawExpression($expression);
        return $this;
    }

    // ── WHERE ─────────────────────────────────────────────────────────────────

    public function where(string $column, mixed $value, string $operator = '='): static
    {
        $allowed = ['=', '!=', '<>', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE', 'IS', 'IS NOT'];
        if (!in_array(strtoupper($operator), $allowed, true)) {
            throw new \InvalidArgumentException("Operador SQL no permitido: $operator");
        }
        $placeholder = ':w_' . str_replace(['.', '`', '-'], '_', $column) . '_' . count($this->wheres);
        $clause      = $this->quoteIdentifier($column) . " $operator $placeholder";
        $this->addWhereClause($clause);
        $this->bindings[$placeholder] = $value;
        return $this;
    }

    public function orWhere(string $column, mixed $value, string $operator = '='): static
    {
        $this->useOrForNext = true;
        return $this->where($column, $value, $operator);
    }

    public function whereNull(string $column): static
    {
        $this->addWhereClause($this->quoteIdentifier($column) . " IS NULL");
        return $this;
    }

    public function whereNotNull(string $column): static
    {
        $this->addWhereClause($this->quoteIdentifier($column) . " IS NOT NULL");
        return $this;
    }

    public function whereIn(string $column, array $values): static
    {
        if (empty($values)) {
            $this->addWhereClause("1 = 0");
            return $this;
        }
        $placeholders = [];
        foreach ($values as $index => $value) {
            $ph = ':wi_' . str_replace(['.', '`'], '_', $column) . '_' . $index . '_' . count($this->wheres);
            $placeholders[]       = $ph;
            $this->bindings[$ph]  = $value;
        }
        $this->addWhereClause($this->quoteIdentifier($column) . " IN (" . implode(', ', $placeholders) . ")");
        return $this;
    }

    public function whereNotIn(string $column, array $values): static
    {
        if (empty($values)) {
            return $this;
        }
        $placeholders = [];
        foreach ($values as $index => $value) {
            $ph = ':wni_' . str_replace(['.', '`'], '_', $column) . '_' . $index . '_' . count($this->wheres);
            $placeholders[]       = $ph;
            $this->bindings[$ph]  = $value;
        }
        $this->addWhereClause($this->quoteIdentifier($column) . " NOT IN (" . implode(', ', $placeholders) . ")");
        return $this;
    }

    public function whereBetween(string $column, mixed $min, mixed $max): static
    {
        $phMin = ':wbt_min_' . count($this->wheres);
        $phMax = ':wbt_max_' . count($this->wheres);
        $this->bindings[$phMin] = $min;
        $this->bindings[$phMax] = $max;
        $this->addWhereClause($this->quoteIdentifier($column) . " BETWEEN $phMin AND $phMax");
        return $this;
    }

    public function whereRaw(string $sql, array $bindings = []): static
    {
        $this->addWhereClause($sql);
        foreach ($bindings as $key => $value) {
            $this->bindings[$key] = $value;
        }
        return $this;
    }

    private function addWhereClause(string $clause): void
    {
        if (!empty($this->wheres) && $this->useOrForNext) {
            $this->wheres[] = ['type' => 'OR', 'sql' => $clause];
        } else {
            $this->wheres[] = ['type' => 'AND', 'sql' => $clause];
        }
        $this->useOrForNext = false;
    }

    private function buildWhereClause(): string
    {
        if (empty($this->wheres)) {
            return '';
        }
        $parts = '';
        foreach ($this->wheres as $i => $where) {
            if ($i === 0) {
                $parts = $where['sql'];
            } else {
                $parts .= ' ' . $where['type'] . ' ' . $where['sql'];
            }
        }
        return ' WHERE ' . $parts;
    }

    // ── JOINs ─────────────────────────────────────────────────────────────────

    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): static
    {
        $this->joins[] = strtoupper($type) . " JOIN "
            . $this->quoteIdentifier($table)
            . " ON " . $this->quoteIdentifier($first)
            . " $operator " . $this->quoteIdentifier($second);
        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    public function rightJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    // ── GROUP BY / HAVING ────────────────────────────────────────────────────

    public function groupBy(string ...$columns): static
    {
        foreach ($columns as $col) {
            $this->groups[] = $this->quoteIdentifier($col);
        }
        return $this;
    }

    public function having(string $raw): static
    {
        $this->havings[] = $raw;
        return $this;
    }

    // ── ORDER / LIMIT / OFFSET ────────────────────────────────────────────────

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $direction      = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orders[] = $this->quoteIdentifier($column) . " $direction";
        return $this;
    }

    public function orderByRaw(string $expression): static
    {
        $this->orders[] = $expression;
        return $this;
    }

    public function latest(string $column = 'created_at'): static { return $this->orderBy($column, 'DESC'); }
    public function oldest(string $column = 'created_at'): static { return $this->orderBy($column, 'ASC'); }

    public function limit(int $limit): static   { $this->limit = $limit; return $this; }
    public function offset(int $offset): static { $this->offset = $offset; return $this; }
    public function take(int $n): static        { return $this->limit($n); }
    public function skip(int $n): static        { return $this->offset($n); }

    // ── Aggregates ───────────────────────────────────────────────────────────

    public function count(string $column = '*'): int
    {
        $expr = $column === '*' ? 'COUNT(*)' : 'COUNT(' . $this->quoteIdentifier($column) . ')';
        return (int) $this->aggregate($expr);
    }

    public function sum(string $column): float|int
    {
        return $this->aggregate('SUM(' . $this->quoteIdentifier($column) . ')') ?? 0;
    }

    public function avg(string $column): float|null
    {
        return $this->aggregate('AVG(' . $this->quoteIdentifier($column) . ')');
    }

    public function min(string $column): mixed
    {
        return $this->aggregate('MIN(' . $this->quoteIdentifier($column) . ')');
    }

    public function max(string $column): mixed
    {
        return $this->aggregate('MAX(' . $this->quoteIdentifier($column) . ')');
    }

    private function aggregate(string $expression): mixed
    {
        $sql  = "SELECT $expression FROM " . $this->quoteIdentifier($this->table);
        $sql .= implode(' ', $this->joins);
        $sql .= $this->buildWhereClause();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->bindings);
        return $stmt->fetchColumn();
    }

    public function exists(): bool  { return $this->count() > 0; }
    public function doesntExist(): bool { return !$this->exists(); }

    // ── Global scopes ─────────────────────────────────────────────────────────

    public function withoutGlobalScope(string $name): static
    {
        $this->skippedGlobalScopes[] = $name;
        return $this;
    }

    // ── Eager loading ─────────────────────────────────────────────────────────

    public function with(array|string $relations): static
    {
        if (is_string($relations)) {
            $this->eagerLoads[$relations] = null;
            return $this;
        }
        foreach ($relations as $key => $value) {
            if (is_int($key)) {
                $this->eagerLoads[$value] = null;
            } else {
                $this->eagerLoads[$key] = $value;
            }
        }
        return $this;
    }

    public function getEagerLoads(): array { return $this->eagerLoads; }

    // ── Local scope delegation ────────────────────────────────────────────────

    public function __call(string $method, array $args): static
    {
        if ($this->modelClass) {
            $scopeMethod = 'scope' . ucfirst($method);
            if (method_exists($this->modelClass, $scopeMethod)) {
                $instance = new $this->modelClass();
                $instance->$scopeMethod($this, ...$args);
                return $this;
            }
        }
        throw new \BadMethodCallException("Method {$method} does not exist on QueryBuilder.");
    }

    // ── SQL building ──────────────────────────────────────────────────────────

    private function buildSelectClause(): string
    {
        if (empty($this->selects)) {
            return 'SELECT *';
        }
        $parts = array_map(function ($col) {
            return $col instanceof RawExpression ? (string)$col : $this->quoteIdentifier($col);
        }, $this->selects);
        return 'SELECT ' . implode(', ', $parts);
    }

    private function buildSql(): string
    {
        $sql  = $this->buildSelectClause();
        $sql .= ' FROM ' . $this->quoteIdentifier($this->table);

        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        $sql .= $this->buildWhereClause();

        if (!empty($this->groups)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groups);
        }

        if (!empty($this->havings)) {
            $sql .= ' HAVING ' . implode(' AND ', $this->havings);
        }

        if (!empty($this->orders)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orders);
        }

        if ($this->limit !== null)  { $sql .= " LIMIT {$this->limit}"; }
        if ($this->offset !== null) { $sql .= " OFFSET {$this->offset}"; }

        return $sql;
    }

    private function quoteIdentifier(string $identifier): string
    {
        if ($identifier === '*') return '*';
        return implode('.', array_map(
            fn($part) => '`' . str_replace('`', '', $part) . '`',
            explode('.', $identifier)
        ));
    }

    private function tracedPrepare(string $sql): \PDOStatement
    {
        if (
            \HexaGen\Core\Config::get('telemetry.trace_queries', false)
            && \HexaGen\Core\Config::get('telemetry.enabled', true)
        ) {
            $span = \HexaGen\Core\Observability\Telemetry::startSpan('db.query', [
                'db.statement' => $sql,
                'db.table'     => $this->table,
            ]);
            \HexaGen\Core\Observability\Telemetry::endSpan($span);
        }
        return $this->pdo->prepare($sql);
    }

    // ── Fetch methods ─────────────────────────────────────────────────────────

    public function get(): array
    {
        $stmt = $this->tracedPrepare($this->buildSql());
        $stmt->execute($this->bindings);
        $results = $stmt->fetchAll();

        if ($this->modelClass === null) {
            return $results;
        }

        $models = array_map(fn($row) => ($this->modelClass)::hydrate($row), $results);

        if (!empty($models) && !empty($this->eagerLoads)) {
            $models = $this->eagerLoadRelations($models);
        }

        return $models;
    }

    public function first(): mixed
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    public function firstOrFail(): mixed
    {
        $result = $this->first();
        if ($result === null) {
            throw new ModelNotFoundException("No record found in [{$this->table}].");
        }
        return $result;
    }

    public function find(mixed $id, string $primaryKey = 'id'): mixed
    {
        return $this->where($primaryKey, $id)->first();
    }

    public function findOrFail(mixed $id, string $primaryKey = 'id'): mixed
    {
        $result = $this->find($id, $primaryKey);
        if ($result === null) {
            throw new ModelNotFoundException("No record found with id={$id} in [{$this->table}].");
        }
        return $result;
    }

    public function pluck(string $column): array
    {
        $original = $this->selects;
        $this->selects = [$column];
        $rows = $this->get();
        $this->selects = $original;
        return array_column($rows, $column);
    }

    public function cursor(): \HexaGen\Core\Support\LazyCollection
    {
        $sql        = $this->buildSql();
        $pdo        = $this->pdo;
        $bindings   = $this->bindings;
        $modelClass = $this->modelClass;

        return \HexaGen\Core\Support\LazyCollection::make(
            function () use ($pdo, $sql, $bindings, $modelClass) {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($bindings);
                $stmt->setFetchMode(\PDO::FETCH_ASSOC);
                while ($row = $stmt->fetch()) {
                    yield $modelClass ? $modelClass::hydrate($row) : $row;
                }
            }
        );
    }

    public function paginate(int $perPage = 15, int $page = 1): \HexaGen\Core\Database\Paginator
    {
        $countSql  = 'SELECT COUNT(*) FROM ' . $this->quoteIdentifier($this->table);
        $countSql .= $this->buildWhereClause();
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($this->bindings);
        $total = (int) $countStmt->fetchColumn();

        $items = $this->limit($perPage)->offset(($page - 1) * $perPage)->get();

        return new Paginator($items, $total, $perPage, $page);
    }

    // ── Write methods ─────────────────────────────────────────────────────────

    public function insert(array $data): bool
    {
        $columns      = implode(', ', array_map(fn($c) => $this->quoteIdentifier($c), array_keys($data)));
        $placeholders = implode(', ', array_map(fn($c) => ':' . $c, array_keys($data)));
        $sql          = "INSERT INTO " . $this->quoteIdentifier($this->table) . " ($columns) VALUES ($placeholders)";
        $stmt         = $this->pdo->prepare($sql);
        $bindings     = [];
        foreach ($data as $key => $val) {
            $bindings[':' . $key] = $val;
        }
        return $stmt->execute($bindings);
    }

    public function insertGetId(array $data): string|false
    {
        $this->insert($data);
        return $this->pdo->lastInsertId();
    }

    /**
     * Update records matching current WHERE clauses.
     * Usage: User::query()->where('active', false)->update(['status' => 'inactive'])
     */
    public function update(array $data): int
    {
        $sets     = [];
        $bindings = $this->bindings;

        foreach ($data as $key => $val) {
            $ph         = ':upd_' . $key;
            $sets[]     = $this->quoteIdentifier($key) . " = $ph";
            $bindings[$ph] = $val;
        }

        $sql  = "UPDATE " . $this->quoteIdentifier($this->table);
        $sql .= " SET " . implode(', ', $sets);
        $sql .= $this->buildWhereClause();

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->rowCount();
    }

    /**
     * Delete records matching current WHERE clauses.
     * Usage: User::query()->where('id', $id)->delete()
     */
    public function delete(): int
    {
        $sql  = "DELETE FROM " . $this->quoteIdentifier($this->table);
        $sql .= $this->buildWhereClause();

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->bindings);
        return $stmt->rowCount();
    }

    public function truncate(): void
    {
        $this->pdo->exec("TRUNCATE TABLE " . $this->quoteIdentifier($this->table));
    }

    // ── Eager loading ─────────────────────────────────────────────────────────

    private function eagerLoadRelations(array $models): array
    {
        foreach ($this->buildEagerLoadTree() as $relationName => $spec) {
            $models = $this->loadOneRelation(
                $models,
                $relationName,
                $spec['constraint'],
                $spec['nested'],
                $this->modelClass
            );
        }
        return $models;
    }

    /** Parses dot-notation eager loads into a nested tree. */
    private function buildEagerLoadTree(array $eagerLoads = []): array
    {
        $source = $eagerLoads ?: $this->eagerLoads;
        $tree   = [];

        foreach ($source as $relation => $constraint) {
            if (str_contains((string) $relation, '.')) {
                [$parent, $rest] = explode('.', $relation, 2);
                $tree[$parent]['nested'][$rest] = $constraint;
                $tree[$parent]['constraint']    ??= null;
            } else {
                $tree[$relation]['constraint'] = $constraint;
                $tree[$relation]['nested']     ??= [];
            }
        }

        return $tree;
    }

    /** Loads one relation level, then recursively loads nested relations. */
    private function loadOneRelation(
        array   $models,
        string  $relationName,
        mixed   $constraint,
        array   $nested,
        string  $modelClass
    ): array {
        $parentInstance = new $modelClass();

        if (!method_exists($parentInstance, $relationName)) {
            throw new \RuntimeException(
                "Relation [{$relationName}] not defined on model [{$modelClass}]."
            );
        }

        $relation = $parentInstance->$relationName();
        if (!$relation instanceof \HexaGen\Core\Database\Relations\Relation) {
            throw new \RuntimeException(
                "Relation method [{$relationName}] must return a Relation instance."
            );
        }

        if ($constraint instanceof \Closure) {
            $relation->applyConstraint($constraint);
        }

        $results = $relation->getRelationResults($models);
        $models  = $relation->match($models, $results, $relationName);

        // Recursively load nested relations (e.g. rooms.images → images on each room)
        if (!empty($nested) && !empty($results)) {
            $relatedClass  = get_class($results[0]);
            $relatedModels = [];

            foreach ($models as $model) {
                $val = $model->$relationName ?? null;
                if ($val === null) continue;
                if (is_array($val)) {
                    foreach ($val as $r) $relatedModels[] = $r;
                } else {
                    $relatedModels[] = $val;
                }
            }

            if (!empty($relatedModels)) {
                foreach ($this->buildEagerLoadTree($nested) as $nestedName => $nestedSpec) {
                    $relatedModels = $this->loadOneRelation(
                        $relatedModels,
                        $nestedName,
                        $nestedSpec['constraint'],
                        $nestedSpec['nested'] ?? [],
                        $relatedClass
                    );
                }

                // Write updated related models back to the parent models
                $idx = 0;
                foreach ($models as $model) {
                    $val = $model->$relationName ?? null;
                    if ($val === null) continue;
                    if (is_array($val)) {
                        $count = count($val);
                        $model->$relationName = array_slice($relatedModels, $idx, $count);
                        $idx += $count;
                    } else {
                        $model->$relationName = $relatedModels[$idx++];
                    }
                }
            }
        }

        return $models;
    }
}
