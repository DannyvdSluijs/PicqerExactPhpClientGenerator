<?php

declare(strict_types=1);

namespace PicqerExactPhpClientGenerator\Extractor;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\NodeVisitorAbstract;
use PhpParser\PrettyPrinter\Standard;

class CodeExtractor extends NodeVisitorAbstract
{
    private const SKIPPED_PROPERTIES = ['fillable', 'primaryKey', 'url'];
    private Standard $printer;
    private ?string $additionalClassDocComment = null;
    private array $functions = [];
    private array $traits = [];
    private array $properties = [];


    public function __construct()
    {
        $this->printer = new Standard();
    }

    public function enterNode(Node $node): void
    {
        if ($node instanceof Class_) {
            $classDocComment = $node->getDocComment()->getText();
            $lines = explode("\n", $classDocComment);
            $lines = array_slice($lines, 5);
            $lines = array_filter($lines, static function ($line) { return ! str_contains($line, '@property'); });
            array_pop($lines);

            if (count($lines) !== 0) {
                $this->additionalClassDocComment = implode("\n", $lines);
            }
        }

        if ($node instanceof ClassMethod) {
            $method = $this->printer->prettyPrint([$node]);
            $this->functions[] = str_replace(["\n", "(isset", "(!isset"], ["\n    ", '( isset', "(! isset"], $method);
        }

        if ($node instanceof TraitUse) {
            $this->traits[] = implode('\\', $node->traits[0]->parts);
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

    public function getAdditionalClassDocComment(): ?string
    {
        return $this->additionalClassDocComment;
    }

    public function getFunctions(): array
    {
        return $this->functions;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getTraits(): array
    {
        return $this->traits;
    }
}