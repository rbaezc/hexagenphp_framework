<?php
namespace HexaGen\Core\Database\Relations;

class HasMany extends Relation
{
    /**
     * Get the query results for the relation using Eager Loading.
     */
    public function getRelationResults(array $models): array
    {
        $localKeys = array_unique(array_filter(array_map(fn($m) => $m->{$this->localKey} ?? null, $models)));
        if (empty($localKeys)) {
            return [];
        }

        $relatedClass = $this->relatedClass;
        return $relatedClass::query()->whereIn($this->foreignKey, $localKeys)->get();
    }

    /**
     * Match the queried relation results back to their parent models.
     */
    public function match(array $models, array $results, string $relationName): array
    {
        $grouped = [];
        foreach ($results as $result) {
            $grouped[$result->{$this->foreignKey}][] = $result;
        }

        foreach ($models as $model) {
            $pkVal = $model->{$this->localKey} ?? null;
            $model->{$relationName} = $grouped[$pkVal] ?? [];
        }

        return $models;
    }
}
