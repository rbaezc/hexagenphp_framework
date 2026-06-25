<?php
namespace HexaGen\Core\Database\Relations;

use HexaGen\Core\Database\Model;

class HasOne extends Relation
{
    public function getRelationResults(array $parentModels): array
    {
        $localKey   = $this->localKey;
        $foreignKey = $this->foreignKey;
        $ids        = array_map(fn($m) => $m->$localKey, $parentModels);

        return $this->relatedClass::query()
            ->whereIn($foreignKey, $ids)
            ->get();
    }

    public function match(array $parentModels, array $results, string $relation): array
    {
        $localKey   = $this->localKey;
        $foreignKey = $this->foreignKey;
        $map        = [];

        foreach ($results as $related) {
            $map[$related->$foreignKey] = $related;
        }

        foreach ($parentModels as $model) {
            $model->$relation = $map[$model->$localKey] ?? null;
        }

        return $parentModels;
    }
}
