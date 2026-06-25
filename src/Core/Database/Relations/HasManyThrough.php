<?php
namespace HexaGen\Core\Database\Relations;

use HexaGen\Core\Database\Model;
use HexaGen\Core\Database\QueryBuilder;

class HasManyThrough extends Relation
{
    public function __construct(
        protected Model $parent,
        protected string $relatedClass,
        protected string $throughClass,
        protected string $firstKey,
        protected string $secondKey,
        protected string $localKey = 'id',
        protected string $secondLocalKey = 'id'
    ) {}

    public function getRelationResults(): array
    {
        $throughTable  = (new $this->throughClass)->getTableName();
        $relatedTable  = (new $this->relatedClass)->getTableName();
        $parentTable   = $this->parent::getTableName();
        $parentKeyVal  = $this->parent->{$this->localKey};

        $sql = "SELECT {$relatedTable}.* FROM {$relatedTable}
                INNER JOIN {$throughTable}
                    ON {$throughTable}.{$this->secondLocalKey} = {$relatedTable}.{$this->secondKey}
                WHERE {$throughTable}.{$this->firstKey} = :parentKey";

        $pdo  = $this->parent::getPdo();
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':parentKey' => $parentKeyVal]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(fn($row) => ($this->relatedClass)::hydrate($row), $rows);
    }

    public function match(array $models): array
    {
        return $models;
    }
}
