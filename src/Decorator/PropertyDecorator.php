<?php

declare(strict_types=1);

namespace PicqerExactPhpClientGenerator\Decorator;

use Symfony\Component\String\Inflector\EnglishInflector;

class PropertyDecorator
{
    /** @var \stdClass */
    private $property;

    public function __construct(\stdClass $property)
    {
        $this->property = $property;
    }

    public function __get(string $param)
    {
        return match ($param) {
            'type' => $this->getType(),
            'description' => $this->getDescription(),
            default => $this->property->{$param}
        };
    }

    public function toPhpDoc(): string
    {
        return trim(sprintf(
            '@property %s $%s %s',
            $this->getType(),
            $this->property->name,
            $this->getDescription(),
        )) . PHP_EOL;
    }

    private function getDescription(): string
    {
        if (str_starts_with($this->property->description, 'http')) {
            return "See {$this->property->description} for more explanation";
        }

        return $this->property->description;
    }

    private function getType(): string
    {
        // Some types are mis-documented
        if (str_starts_with($this->property->type, 'Class_') || $this->property->type === 'Exact.Web.Api.Models.HRM.DivisionClass') {
            return 'DivisionClass';
        }
        if ($this->property->type === 'Attachments') {
            return 'DocumentAttachment[]';
        }

        if ($this->property->type === 'Exact.Web.Api.Models.Manufacturing.MaterialPlanCalculator') {
            return 'string';
        }

        if (str_starts_with($this->property->description, 'Collection') || ! str_starts_with($this->property->type, 'Edm.')) {
            // Some cases aren't properly handled by inflector
            $exceptions = [
                'EmploymentContractFlexPhases' => 'EmploymentContractFlexPhase',
                'ItemWarehouses' => 'ItemWarehouse',
                'Warehouses' => 'Warehouse',
            ];

            if (array_key_exists($this->property->type, $exceptions)) {
                return $exceptions[$this->property->type] . '[]';
            }

            $inflector = new EnglishInflector();
            $singleOptions = $inflector->singularize($this->property->type);

            return array_pop($singleOptions) . '[]';
        }

        return match ($this->property->type) {
            'Edm.Int64', 'Edm.Int32', 'Edm.Int16', 'Edm.Byte' => 'int',
            'Edm.Double' => 'float',
            'Edm.Boolean' => 'bool',
            default => 'string'
        };
    }
}