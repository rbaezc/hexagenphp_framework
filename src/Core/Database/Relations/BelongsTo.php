<?php
namespace HexaGen\Core\Database\Relations;

class BelongsTo extends Relation
{
    /**
     * Get the query results for the relation using Eager Loading.
     */
    public function getRelationResults(array $models): array
    {
        $foreignKeys = array_unique(array_filter(array_map(fn($m) => $m->{$this->foreignKey} ?? null, $models)));
        if (empty($foreignKeys)) {
            return [];
        }

        $relatedClass = $this->relatedClass;
        return $relatedClass::query()->whereIn($this->localKey, $foreignKeys)->get();
    }

    /**
     * Match the queried relation results back to their parent models.
     */
    public function match(array $models, array $results, string $relationName): array
    {
        $mapped = [];
        foreach ($results as $result) {
            $mapped[$result->{$this->localKey}] = $result;
        }

        foreach ($models as $model) {
            $fkVal = $model->{$this->foreignKey} ?? null;
            $model->{$relationName} = $mapped[$fkVal] ?? null;
        }

        return $models;
    }
}
