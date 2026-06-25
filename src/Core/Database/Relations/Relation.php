<?php
namespace HexaGen\Core\Database\Relations;

use HexaGen\Core\Database\Model;

abstract class Relation
{
    protected Model $parent;
    protected string $relatedClass;
    protected string $foreignKey;
    protected string $localKey;

    public function __construct(Model $parent, string $relatedClass, string $foreignKey, string $localKey)
    {
        $this->parent = $parent;
        $this->relatedClass = $relatedClass;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
    }

    /**
     * Get the query results for the relation using Eager Loading (1 query via IN).
     */
    abstract public function getRelationResults(array $models): array;

    /**
     * Match the queried relation results back to their parent models.
     */
    abstract public function match(array $models, array $results, string $relationName): array;
}
