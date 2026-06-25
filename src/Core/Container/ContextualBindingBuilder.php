<?php
namespace HexaGen\Core\Container;

class ContextualBindingBuilder
{
    private string $concrete;
    private string $needs;

    public function __construct(
        private ContextualBindingRegistry $registry,
        string $concrete
    ) {
        $this->concrete = $concrete;
    }

    public function needs(string $abstract): static
    {
        $this->needs = $abstract;
        return $this;
    }

    public function give(string|\Closure $implementation): void
    {
        $this->registry->bind($this->concrete, $this->needs, $implementation);
    }
}
