<?php
namespace HexaGen\Core\Database\Relations;

use HexaGen\Core\Database\Model;

class MorphMany extends Relation
{
    public function __construct(
        protected Model $parent,
        protected string $relatedClass,
        protected string $morphName,
        protected string $localKey = 'id'
    ) {}

    public function getRelationResults(): array
    {
        $relatedTable = (new $this->relatedClass)->getTableName();
        $typeColumn   = $this->morphName . '_type';
        $idColumn     = $this->morphName . '_id';
        $parentType   = static::class === MorphMany::class
            ? get_class($this->parent)
            : get_class($this->parent);
        $parentId     = $this->parent->{$this->localKey};

        $pdo  = $this->parent::getPdo();
        $sql  = "SELECT * FROM `{$relatedTable}` WHERE `{$typeColumn}` = :type AND `{$idColumn}` = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':type' => get_class($this->parent), ':id' => $parentId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(fn($row) => ($this->relatedClass)::hydrate($row), $rows);
    }

    public function match(array $models): array
    {
        return $models;
    }
}
