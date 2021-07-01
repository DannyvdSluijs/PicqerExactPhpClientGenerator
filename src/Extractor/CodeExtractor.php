<?php

declare(strict_types=1);

namespace PicqerExactPhpClientGenerator\Extractor;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeVisitorAbstract;
use PhpParser\PrettyPrinter\Standard;

class CodeExtractor extends NodeVisitorAbstract
{
    private const SKIPPED_PROPERTIES = ['fillable', 'primaryKey', 'url'];
    private Standard $printer;
    private array $functions = [];
    private array $properties = [];


    public function __construct()
    {
        $this->printer = new Standard();
    }

    public function enterNode(Node $node): void
    {
        if ($node instanceof ClassMethod) {
            $method = $this->printer->prettyPrint([$node]);
            $this->functions[] = str_replace(["\n", "(isset", "(!isset"], ["\n    ", '( isset', "(! isset"], $method);
        }

        if ($node instanceof Property) {
            $result = array_filter($node->props, function (Node\Stmt\PropertyProperty $prop) {
                return in_array($prop->name->name, self::SKIPPED_PROPERTIES);
            });
            if (count($result) > 0) {
                return;
            }

            $property = $this->printer->prettyPrint([$node]);
            $this->properties[] = $property;
        }
    }

    public function getFunctions(): array
    {
        return $this->functions;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }
}