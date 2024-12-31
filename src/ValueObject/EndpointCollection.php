<?php

declare(strict_types=1);

namespace PicqerExactPhpClientGenerator\ValueObject;

use Traversable;

/**
 * @implements \IteratorAggregate<Endpoint>
 */
readonly class EndpointCollection implements \IteratorAggregate
{
    /** @var array<string, Endpoint> */
    private array $items;

    public function __construct(Endpoint ...$items)
    {
        $this->items = $items;
    }

    public function filter(callable $filter): self
    {
        return new self(...array_filter($this->items, $filter));
    }

    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->items);
    }
}
