<?php

declare(strict_types=1);

namespace PicqerExactPhpClientGenerator\Command;

use MetaDataTool\Command\MetaDataBuilderCommand;
use PicqerExactPhpClientGenerator\Decorator\EndpointDecorator;
use PicqerExactPhpClientGenerator\Extractor\CodeExtractor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Templating\Helper\SlotsHelper;
use Symfony\Component\Templating\Loader\FilesystemLoader;
use Symfony\Component\Templating\PhpEngine;
use Symfony\Component\Templating\TemplateNameParser;
use Throwable;

class ModelGenerateCommand extends Command
{
    protected static string $defaultName = 'run';
    protected static string $metaDataFile = '.meta/meta-data.json';

    private InputInterface $input;
    private OutputInterface $output;

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
        $this->input = $input;
        $this->output = $output;

        $this->refreshMetaDataIfNeeded();

        $data = file_get_contents(self::$metaDataFile);
        $json = (array) json_decode($data, false, 512, JSON_THROW_ON_ERROR);

        if ($input->getOption('endpoint')) {
            $desiredEndpoint = $input->getOption('endpoint');
            $endpoints = array_filter($json, static function($endpoint) use ($desiredEndpoint) {
                return $endpoint->endpoint === $desiredEndpoint;
            });
        } else {
            $endpoints = $json;
        }

        $filesystemLoader = new FilesystemLoader('resources/views/%name%');
        $templating = new PhpEngine(new TemplateNameParser(), $filesystemLoader);
        $templating->set(new SlotsHelper());

        foreach ($endpoints as $endpoint) {
            $output->writeln(sprintf('Processing endpoint "%s"', $endpoint->endpoint));

            $decoratedEndpoint = new EndpointDecorator($endpoint, $input->getArgument('sources'));
            $filename = $decoratedEndpoint->getFilename();

            if (is_readable($filename)) {
                $extractor = new CodeExtractor($filename);
                $codeExtract = $extractor->extract();
            }

            $src = $templating->render('model.php', [
                'endpoint' => $decoratedEndpoint,
                'deprecationDocComment' => $codeExtract->getDeprecationDocComment(),
                'additionalClassDocComment' => $codeExtract->getAdditionalClassDocComment(),
                'properties' => $codeExtract->getProperties(),
                'traits' => $codeExtract->getTraits(),
                'functions' => $codeExtract->getFunctions(),
            ]);

            file_put_contents($filename, "<?php\n\n$src");
            $output->writeln(sprintf('Updated/Created file "%s" for endpoint "%s"', $filename, $endpoint->endpoint));
        }

        return 0;
    }

    private function refreshMetaDataIfNeeded(): void
    {
        if ($this->input->getOption('refresh-meta-data') || !is_readable(self::$metaDataFile)) {
            $this->output->writeln('Refreshing exact online meta data (This might takes some time)');

            $metaDataCommand = new MetaDataBuilderCommand();
            $metaDataCommand->run(
                new ArrayInput(['--destination' => dirname(self::$metaDataFile)], $metaDataCommand->getDefinition()),
                $this->input->getOption('verbose') ? $this->output : new NullOutput(),
            );

            $this->output->writeln('Refreshed exact online meta data');
        }
    }
}