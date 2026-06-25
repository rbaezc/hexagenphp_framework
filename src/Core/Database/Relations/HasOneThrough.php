<?php
namespace HexaGen\Core\Database\Relations;

use HexaGen\Core\Database\Model;

class HasOneThrough extends HasManyThrough
{
    public function getRelationResults(): ?Model
    {
        $results = parent::getRelationResults();
        return $results[0] ?? null;
    }
}
