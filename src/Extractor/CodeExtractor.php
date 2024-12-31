<?php

declare(strict_types=1);

namespace PicqerExactPhpClientGenerator\Extractor;


use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\Lexer;
use PicqerExactPhpClientGenerator\Extractor\ValueObject\CodeExtract;
use Throwable;

readonly class CodeExtractor
{

    public function __construct(
        private string $filename
    )
    {
    }

    public function extract(): CodeExtract
    {
        $factory = new ParserFactory();
        $parser = $factory->createForNewestSupportedVersion();

        $code = file_get_contents($this->filename);
        try {
            $ast = $parser->parse($code);
        } catch (Throwable $error) {
            throw new \RuntimeException("An error occurred ({$error->getMessage()}) while parsing code from $this->filename");
        }

        $traverser = new NodeTraverser();
        $codeExtractor = new CodeExtractorNodeVisitor($this->filename);
        $traverser->addVisitor($codeExtractor);

        $traverser->traverse($ast);

        return $codeExtractor->getCodeExtract();
    }
}