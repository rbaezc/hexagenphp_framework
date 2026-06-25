<?php
namespace HexaGen\Core\Database\Relations;

use HexaGen\Core\Database\DatabaseConnection;
use HexaGen\Core\Database\Model;
use HexaGen\Core\Database\QueryBuilder;
use PDO;

/**
 * Many-to-many relation via a pivot table.
 *
 * Uso en el modelo:
 *   protected function roles(): BelongsToMany {
 *       return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id');
 *   }
 */
class BelongsToMany extends Relation
{
    public function __construct(
        Model  $parent,
        string $relatedClass,
        private string $pivotTable,
        string $foreignKey,
        string $localKey,
    ) {
        parent::__construct($parent, $relatedClass, $foreignKey, $localKey);
    }

    public function getRelationResults(array $parentModels): array
    {
        $pdo        = (new DatabaseConnection())->getPdo();
        $localKey   = $this->localKey;
        $foreignKey = $this->foreignKey;
        $related    = $this->relatedClass;
        $relatedPk  = (new $related())->getRouteKeyName();
        $ids        = array_map(fn($m) => $m->$localKey, $parentModels);

        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("
            SELECT r.*, p.$foreignKey AS __pivot_parent_id
            FROM `{$related::getTableName()}` r
            INNER JOIN `{$this->pivotTable}` p ON p.$localKey = r.$relatedPk
            WHERE p.$foreignKey IN ($placeholders)
        ");
        $stmt->execute(array_values($ids));

        return array_map(fn($row) => $related::hydrate($row), $stmt->fetchAll());
    }

    public function match(array $parentModels, array $results, string $relation): array
    {
        $foreignKey = $this->foreignKey;
        $map        = [];
        foreach ($results as $related) {
            $map[$related->__pivot_parent_id][] = $related;
        }
        foreach ($parentModels as $model) {
            $model->$relation = $map[$model->{$this->localKey}] ?? [];
        }
        return $parentModels;
    }

    public function attach(int|string $parentId, int|string $relatedId): void
    {
        $pdo = (new DatabaseConnection())->getPdo();
        $pdo->prepare("INSERT OR IGNORE INTO `{$this->pivotTable}` ({$this->foreignKey}, {$this->localKey}) VALUES (?, ?)")
            ->execute([$parentId, $relatedId]);
    }

    public function detach(int|string $parentId, int|string $relatedId): void
    {
        $pdo = (new DatabaseConnection())->getPdo();
        $pdo->prepare("DELETE FROM `{$this->pivotTable}` WHERE {$this->foreignKey} = ? AND {$this->localKey} = ?")
            ->execute([$parentId, $relatedId]);
    }
}
