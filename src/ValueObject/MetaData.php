<?php

declare(strict_types=1);

namespace PicqerExactPhpClientGenerator\ValueObject;

readonly class MetaData
{
    public function __construct(
        public EndpointCollection $endpoints,
    ){}

    public static function fromStdClass(array $stdClass): self
    {
        return new self(
            new EndpointCollection(
                ...array_map(
                    Endpoint::fromStdClass(...),
                    $stdClass
                )
            ),
        );
    }
}
