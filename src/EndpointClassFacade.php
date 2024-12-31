<?php

declare(strict_types=1);

namespace PicqerExactPhpClientGenerator;

use PicqerExactPhpClientGenerator\Decorator\EndpointDecorator;use PicqerExactPhpClientGenerator\Extractor\CodeExtractor;
use PicqerExactPhpClientGenerator\Extractor\ValueObject\CodeExtract;
use PicqerExactPhpClientGenerator\ValueObject\Endpoint;
use PicqerExactPhpClientGenerator\ValueObject\Property;

/**
 * @property ?int strictTypes
 * @property string filename
 * @property bool deprecated
 * @property array nonObsoleteProperties
 * @property array properties
 * @property null|Property primaryKeyProperty
 */
readonly class EndpointClassFacade
{
    private EndpointDecorator $endpointDecorator;
    private CodeExtract $codeExtract;

    public function __construct(
        public Endpoint $endpoint,
        string $path,
    ) {
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
            'isDeprecated' => $this->endpoint->isDeprecated,
            'filename' => $this->endpointDecorator->getFileName(),
            'strictTypes' => $this->codeExtract->getStrictType(),
            'className' => $this->endpointDecorator->getClassName(),
            'documentation' => $this->endpoint->documentation,
            'deprecationDocComment' => $this->codeExtract->getDeprecationDocComment(),
            'additionalClassDocComment' => $this->codeExtract->getAdditionalClassDocComment(),
            'deprecated' => $this->endpoint->isDeprecated,
            'nonObsoleteProperties' => $this->endpointDecorator->getNonObsoleteProperties(),
            'traits' => $this->codeExtract->getTraits(),
            'primaryKeyProperty' => $this->endpointDecorator->primaryKeyProperty(),
            'properties' => $this->codeExtract->getProperties(),
            'functions' => $this->codeExtract->getFunctions(),
            'nonEdmProperties' => $this->endpoint->properties->filter(fn (Property $p): bool => !str_starts_with($p->type, 'Edm.')),
            'uri' => $this->endpoint->uri,
            'name' => $this->endpoint->name,
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
