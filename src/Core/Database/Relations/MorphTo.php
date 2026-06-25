<?php
namespace HexaGen\Core\Database\Relations;

use HexaGen\Core\Database\Model;

class MorphTo extends Relation
{
    public function __construct(
        protected Model $parent,
        protected string $morphType,
        protected string $morphId,
        protected string $ownerKey = 'id'
    ) {}

    public function getRelationResults(): ?Model
    {
        $type = $this->parent->{$this->morphType} ?? null;
        $id   = $this->parent->{$this->morphId}   ?? null;

        if ($type === null || $id === null) {
            return null;
        }

        if (!class_exists($type) || !is_subclass_of($type, Model::class)) {
            return null;
        }

        return $type::find($id);
    }

    public function match(array $models): array
    {
        return $models;
    }
}
