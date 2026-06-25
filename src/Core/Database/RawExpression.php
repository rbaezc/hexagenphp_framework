<?php
namespace HexaGen\Core\Database;

class RawExpression
{
    public function __construct(private string $value) {}

    public function __toString(): string
    {
        return $this->value;
    }
}
