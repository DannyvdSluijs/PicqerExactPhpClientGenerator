<?php

declare(strict_types=1);

namespace PicqerExactPhpClientGenerator\ValueObject;

readonly class SupportedMethods
{
    public function __construct(
        public bool $get,
        public bool $post,
        public bool $put,
        public bool $delete,
    ){}

    public static function fromStdClass(\stdClass $stdClass): self
    {
        return new self(
            $stdClass->get,
            $stdClass->post,
            $stdClass->put,
            $stdClass->delete,
        );
    }
}
