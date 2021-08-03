<?php

declare(strict_types=1);

namespace PicqerExactPhpClientGenerator\Extractor\ValueObject;

class CodeExtract
{
    public function __construct(
        private ?string $deprecationDocComment = null,
        private ?string $additionalClassDocComment = null,
        private array $functions = [],
        private array $traits = [],
        private array $properties = [],
    )
    {
    }

    public function getDeprecationDocComment(): ?string
    {
        return $this->deprecationDocComment;
    }

    public function getAdditionalClassDocComment(): ?string
    {
        return $this->additionalClassDocComment;
    }

    public function getFunctions(): array
    {
        return $this->functions;
    }

    public function getTraits(): array
    {
        return $this->traits;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }
}