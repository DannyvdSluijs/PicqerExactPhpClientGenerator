<?php

declare(strict_types=1);

namespace PicqerExactPhpClientGenerator\Extractor;


use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Declare_;
use PhpParser\Node\Stmt\DeclareDeclare;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\NodeVisitorAbstract;
use PhpParser\PrettyPrinter\Standard;
use PicqerExactPhpClientGenerator\Extractor\ValueObject\CodeExtract;

class CodeExtractorNodeVisitor extends NodeVisitorAbstract
{
    private const SKIPPED_PROPERTIES = ['fillable', 'primaryKey', 'url'];
    private Standard $printer;
    private ?int $strictTypes = null;
    private ?string $deprecationDocComment = null;
    private ?string $additionalClassDocComment = null;
    private array $functions = [];
    private array $traits = [];
    private array $properties = [];

    public function __construct(private string $filename)
    {
        $this->printer = new Standard();
    }

    public function enterNode(Node $node): void
    {
        if ($node instanceof Declare_) {
            $matches = array_filter($node->declares, static fn(DeclareDeclare $n) => $n->key->name === 'strict_types');
            $match = array_shift($matches);

            $this->strictTypes = $match->value->value;
        }
        if ($node instanceof Class_) {
            $classDocComment = $node->getDocComment()->getText();
            $lines = explode("\n", $classDocComment);

            $matches = array_filter($lines, static function ($line): bool { return str_contains($line, '@deprecated'); });
            $this->deprecationDocComment = array_pop($matches);


            $pos = strrpos($classDocComment, '@property');
            if ($pos !== false) {
                $section = substr($classDocComment, $pos);
                $lines = explode("\n", $section);
                array_shift($lines);
                array_pop($lines);

                if (count($lines) !== 0) {
                    $this->additionalClassDocComment = implode("\n", $lines);
                }
            }
        }

        if ($node instanceof ClassMethod) {
            $this->functions[] = $this->readCodeFromFile($node);
        }

        if ($node instanceof TraitUse) {
            $this->traits[] = implode('\\', $node->traits[0]->parts);
        }

        if ($node instanceof Property) {
            $result = array_filter($node->props, function (Node\Stmt\PropertyProperty $prop): bool {
                return in_array($prop->name->name, self::SKIPPED_PROPERTIES);
            });
            if (count($result) > 0) {
                return;
            }

            $property = $this->printer->prettyPrint([$node]);
            $this->properties[] = $property;
        }
    }

    public function getCodeExtract(): CodeExtract
    {
        return new CodeExtract(
            $this->strictTypes,
            $this->deprecationDocComment,
            $this->additionalClassDocComment,
            $this->functions,
            $this->traits,
            $this->properties
        );
    }

    private function readCodeFromFile(Node $node): string
    {
        $startLine = $node->getStartLine();
        $endLine = $node->getEndline();

        $attributes = $node->getAttributes();
        if (array_key_exists('comments', $attributes)) {
            $startLine = $attributes['comments'][0]->getStartLine();
        }

        $contents = file_get_contents($this->filename);
        $lines = explode("\n", $contents);

        $matches = array_slice($lines, $startLine - 1, $endLine - ($startLine - 1));

        return implode("\n", $matches);
    }
}