<?php

declare(strict_types=1);

namespace PicqerExactPhpClientGenerator\Command;

use MetaDataTool\Command\MetaDataBuilderCommand;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\Lexer;
use PicqerExactPhpClientGenerator\Decorator\EndpointDecorator;
use PicqerExactPhpClientGenerator\Extractor\CodeExtractor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\String\Inflector\EnglishInflector;
use Symfony\Component\Templating\Helper\SlotsHelper;
use Symfony\Component\Templating\Loader\FilesystemLoader;
use Symfony\Component\Templating\PhpEngine;
use Symfony\Component\Templating\TemplateNameParser;
use Throwable;

class ModelGenerateCommand extends Command
{
    protected static string $defaultName = 'run';
    protected static string $metaDataFile = '.meta/meta-data.json';
    private EnglishInflector $inflector;

    protected function configure(): void
    {
        $this
            ->setDescription('Generate the Picqer exact php client models')
            ->setHelp(<<<'HELP'
                Scans the online ExactOnline documentation allowing generating the Picqer exact php client models
HELP
            )->setDefinition([
                new InputArgument('sources', InputArgument::REQUIRED, 'The filepath to picqer exact php client sources'),
                new InputOption('refresh-meta-data', 'R', InputOption::VALUE_NONE, 'Force meta data refresh'),
                new InputOption('endpoint', 'E', InputOption::VALUE_REQUIRED, 'Process a single endpoint'),
            ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('refresh-meta-data') || !is_readable(self::$metaDataFile)) {
            $output->writeln('Refreshing exact online meta data (This might takes some time)');

            $metaDataCommand = new MetaDataBuilderCommand();
            $metaDataCommand->run(
                new ArrayInput(['--destination' => dirname(self::$metaDataFile)], $metaDataCommand->getDefinition()),
                $input->getOption('verbose') ? $output : new NullOutput(),
            );

            $output->writeln('Refreshed exact online meta data');
        }

        $data = file_get_contents(self::$metaDataFile);
        $json = (array) json_decode($data, false, 512, JSON_THROW_ON_ERROR);

        $filesystemLoader = new FilesystemLoader('resources/views/%name%');
        $templating = new PhpEngine(new TemplateNameParser(), $filesystemLoader);
        $templating->set(new SlotsHelper());


        if ($input->getOption('endpoint')) {
            $desiredEndpoint = $input->getOption('endpoint');
            $endpoints = array_filter($json, static function($endpoint) use ($desiredEndpoint) {
                return $endpoint->endpoint === $desiredEndpoint;
            });
        } else {
            $endpoints = $json;
        }

        foreach ($endpoints as $endpoint) {
            $decoratedEndpoint = new EndpointDecorator($endpoint, $input->getArgument('sources'));
            $filename = $decoratedEndpoint->getFilename();

            if (is_readable($filename)) {
                [$additionalClassDocComment, $properties, $traits, $methods] = $this->extractExistingCode($filename);
            }

            $src = $templating->render('model.php', [
                'endpoint' => $decoratedEndpoint,
                'additionalClassDocComment' => $additionalClassDocComment,
                'properties' => $properties,
                'traits' => $traits,
                'methods' => $methods,
            ]);

            file_put_contents($filename, "<?php\n\n$src");
            $output->writeln(sprintf('Updated/Created file "%s" for endpoint "%s"', $filename, $endpoint->endpoint));
        }

        return 0;
    }

    private function getInflector(): EnglishInflector
    {
        if (is_null($this->inflector)) {
            $this->inflector = new EnglishInflector();
        }

        return $this->inflector;
    }

    private function extractExistingCode(string $filename): array
    {
        $lexer = new Lexer\Emulative([
            'usedAttributes' => [
                'comments',
                'startLine', 'endLine',
                'startTokenPos', 'endTokenPos',
            ],
        ]);
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7, $lexer);

        $code = file_get_contents($filename);
        try {
            $ast = $parser->parse($code);
        } catch (Throwable $error) {
            throw new \RuntimeException("An error occurred ({$error->getMessage()}) while parsing code from $filename");
        }

        $traverser = new NodeTraverser();
        $codeExtractor = new CodeExtractor();
        $traverser->addVisitor($codeExtractor);

        $traverser->traverse($ast);

        return [$codeExtractor->getAdditionalClassDocComment(), $codeExtractor->getProperties(), $codeExtractor->getTraits(), $codeExtractor->getFunctions()];
    }
}