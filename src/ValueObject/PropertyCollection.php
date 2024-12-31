<?php

declare(strict_types=1);

namespace PicqerExactPhpClientGenerator\ValueObject;

use Traversable;

/**
* @implements \IteratorAggregate<Property>
 */
readonly class PropertyCollection implements \Countable, \IteratorAggregate
{
    /** @var array<string, Property> */
    private array $items;

    public function __construct(Property ...$items)
    {
        $this->items = $items;
    }

    public function filter(callable $filter): self
    {
        return new self(...array_filter($this->items, $filter));
    }

    public function first(): ?Property
    {
        return $this->items[0] ?? null;
    }

    public function last(): ?Property
    {
        return $this->items[array_key_last($this->items)] ?? null;
    }

    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }
}
