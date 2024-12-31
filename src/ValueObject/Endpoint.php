<?php

declare(strict_types=1);

namespace PicqerExactPhpClientGenerator\ValueObject;

readonly class Endpoint
{
    public function __construct(
        public string $name,
        public string $documentation,
        public string $scope,
        public string $uri,
        public SupportedMethods $supportedMethods,
        public string $example,
        public PropertyCollection $properties,
        public bool $isDeprecated,
    ){}

    public static function fromStdClass(\stdClass $stdClass): self
    {
        return new self(
            $stdClass->endpoint,
            $stdClass->documentation,
            $stdClass->scope,
            $stdClass->uri,
            SupportedMethods::fromStdClass($stdClass->supportedMethods),
            $stdClass->example,
            new PropertyCollection(
                ...array_map(
                    Property::fromStdClass(...),
                    $stdClass->properties
                )
            ),
            $stdClass->deprecated,
        );
    }
}
