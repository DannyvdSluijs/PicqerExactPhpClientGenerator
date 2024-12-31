<?php

declare(strict_types=1);

namespace PicqerExactPhpClientGenerator\ValueObject;

readonly class Property
{
    public function __construct(
        public string $name,
        public string $type,
        public string $description,
        public bool $isPrimaryKey,
        public bool $isHidden,
        public bool $isMandatory,
    ){}

    public static function fromStdClass(\stdClass $stdClass): self
    {
        return new self(
            $stdClass->name,
            $stdClass->type,
            $stdClass->description,
            $stdClass->primaryKey,
            $stdClass->hidden,
            $stdClass->mandatory,
        );
    }
}
