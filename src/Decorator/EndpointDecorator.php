<?php

declare(strict_types=1);

namespace PicqerExactPhpClientGenerator\Decorator;

use PicqerExactPhpClientGenerator\NamingHelper;
use PicqerExactPhpClientGenerator\ValueObject\Endpoint;
use PicqerExactPhpClientGenerator\ValueObject\Property;
use PicqerExactPhpClientGenerator\ValueObject\PropertyCollection;
use Symfony\Component\String\Inflector\EnglishInflector;

/**
 * @property string documentation
 */
class EndpointDecorator
{
    private ?EnglishInflector $inflector;

    public function __construct(
        private Endpoint $endpoint,
        private string $path
    )
    {
    }

    public function getFilename(): string
    {
        return sprintf(
            '%s/src/Picqer/Financials/Exact/%s.php',
            $this->path,
            NamingHelper::getClassName($this->endpoint)
        );
    }

    public function getNonObsoleteProperties(): PropertyCollection
    {
        return $this->endpoint->properties->filter(
            fn(Property $p):bool => !$p->isHidden && !str_contains(strtolower($p->description), 'obsolete')
        );
    }

    public function getClassUri(): string
    {
        if (str_starts_with($this->endpoint->uri, '/api/v1/{division}/')) {
            return substr($this->endpoint->uri, 19);
        }

        if (str_starts_with($this->endpoint->uri, '/api/v1/')) {
            return substr($this->endpoint->uri, 8);
        }

        return  $this->endpoint->uri;
    }

    public function hasNonDefaultPrimaryKeyProperty(): bool
    {
        $primaryKeyProperty = $this->primaryKeyProperty();
        return $primaryKeyProperty && $primaryKeyProperty->name !== 'ID';
    }

    public function primaryKeyProperty(): ?Property
    {
        $primaryKeyProperties = $this->endpoint->properties->filter(fn(Property $p): bool => $p->isPrimaryKey);

        if (count($primaryKeyProperties) === 0) {
            return null;
        }

        return $primaryKeyProperties->last();
    }

    public function supportsPostMethod(): bool
    {
        return $this->endpoint->supportedMethods->post;
    }

    private function getInflector(): EnglishInflector
    {
        if (!isset($this->inflector)) {
            $this->inflector = new EnglishInflector();
        }

        return $this->inflector;
    }
}