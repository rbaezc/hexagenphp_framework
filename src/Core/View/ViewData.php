<?php
namespace HexaGen\Core\View;

class ViewData
{
    private array $data;

    public function __construct(array $initial = [])
    {
        $this->data = $initial;
    }

    public function with(string|array $key, mixed $value = null): static
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }
        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
