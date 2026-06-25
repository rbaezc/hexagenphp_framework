<?php
namespace HexaGen\Core\Database\Relations;

use HexaGen\Core\Database\Model;

class MorphOne extends Relation
{
    public function __construct(
        protected Model $parent,
        protected string $relatedClass,
        protected string $morphName,
        protected string $localKey = 'id'
    ) {}

    public function getRelationResults(): ?Model
    {
        $results = (new MorphMany(
            $this->parent,
            $this->relatedClass,
            $this->morphName,
            $this->localKey
        ))->getRelationResults();

        return $results[0] ?? null;
    }

    public function match(array $models): array
    {
        return $models;
    }
}
