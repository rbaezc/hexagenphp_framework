<?php
namespace HexaGen\Core\Database\Schema;

class ColumnDefinition
{
    public string  $name;
    public string  $type;
    public bool    $nullable      = false;
    public bool    $unsigned      = false;
    public bool    $autoIncrement = false;
    public bool    $primary       = false;
    public bool    $unique        = false;
    public bool    $index         = false;
    public mixed   $default       = null;
    public bool    $hasDefault    = false;
    public ?string $comment       = null;
    public ?int    $length        = null;
    public ?int    $total         = null;
    public ?int    $places        = null;
    public array   $allowed       = [];   // for enum

    public function __construct(string $name, string $type)
    {
        $this->name = $name;
        $this->type = $type;
    }

    public function nullable(bool $value = true): static
    {
        $this->nullable = $value;
        return $this;
    }

    public function default(mixed $value): static
    {
        $this->default    = $value;
        $this->hasDefault = true;
        return $this;
    }

    public function unique(): static
    {
        $this->unique = true;
        return $this;
    }

    public function index(): static
    {
        $this->index = true;
        return $this;
    }

    public function unsigned(): static
    {
        $this->unsigned = true;
        return $this;
    }

    public function autoIncrement(): static
    {
        $this->autoIncrement = true;
        return $this;
    }

    public function comment(string $text): static
    {
        $this->comment = $text;
        return $this;
    }
}
