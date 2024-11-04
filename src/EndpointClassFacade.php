<?php

declare(strict_types=1);

namespace PicqerExactPhpClientGenerator;

use PicqerExactPhpClientGenerator\Decorator\EndpointDecorator;
use PicqerExactPhpClientGenerator\Decorator\PropertyDecorator;
use PicqerExactPhpClientGenerator\Extractor\CodeExtractor;
use PicqerExactPhpClientGenerator\Extractor\ValueObject\CodeExtract;

/**
 * @property string filename
 * @property bool deprecated
 * @property array nonObsoleteProperties
 * @property array properties
 * @property null|PropertyDecorator primaryKeyProperty
 */
readonly class EndpointClassFacade
{
    private EndpointDecorator $endpointDecorator;
    private CodeExtract $codeExtract;

    public function __construct(
        \stdClass $endpoint,
        string $path,
    )
    {
        $this->endpointDecorator = new EndpointDecorator($endpoint, $path);

        if (file_exists($this->filename)) {
            $extractor = new CodeExtractor($this->filename);
            $this->codeExtract = $extractor->extract();
        } else {
            $this->codeExtract = new CodeExtract();
        }
    }

    public function __get(string $name)
    {
        return match($name) {
            'filename' => $this->endpointDecorator->getFileName(),
            'strictTypes' => $this->codeExtract->getStrictType(),
            'className' => $this->endpointDecorator->getClassName(),
            'documentation' => $this->endpointDecorator->documentation,
            'deprecationDocComment' => $this->codeExtract->getDeprecationDocComment(),
            'additionalClassDocComment' => $this->codeExtract->getAdditionalClassDocComment(),
            'deprecated' => $this->endpointDecorator->deprecated,
            'nonObsoleteProperties' => $this->endpointDecorator->getNonObsoleteProperties(),
            'traits' => $this->codeExtract->getTraits(),
            'primaryKeyProperty' => $this->endpointDecorator->primaryKeyProperty(),
            'properties' => $this->codeExtract->getProperties(),
            'functions' => $this->codeExtract->getFunctions(),
        };
    }

    public function supportsPostMethod(): bool
    {
        return $this->endpointDecorator->supportsPostMethod();
    }

    public function supportsPutMethod(): bool
    {
        return $this->endpointDecorator->supportsPostMethod();
    }

    public function hasNonDefaultPrimaryKeyProperty(): bool
    {
        return $this->endpointDecorator->hasNonDefaultPrimaryKeyProperty();
    }

    public function getClassUri(): string
    {
        return $this->endpointDecorator->getClassUri();
    }
}
