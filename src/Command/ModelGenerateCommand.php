<?php

declare(strict_types=1);

namespace PicqerExactPhpClientGenerator\Command;

use MetaDataTool\Command\MetaDataBuilderCommand;
use PicqerExactPhpClientGenerator\EndpointClassFacade;
use PicqerExactPhpClientGenerator\ValueObject\Endpoint;
use PicqerExactPhpClientGenerator\ValueObject\MetaData;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Templating\Helper\SlotsHelper;
use Symfony\Component\Templating\Loader\FilesystemLoader;
use Symfony\Component\Templating\PhpEngine;
use Symfony\Component\Templating\TemplateNameParser;

class ModelGenerateCommand extends Command
{
    protected static $defaultName = 'run';
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

        $metaData = $this->loadMetaData();

        $endpoints = $metaData->endpoints;
        if ($input->getOption('endpoint')) {
            $endpoints = $endpoints->filter(fn(Endpoint $endpoint) => $endpoint->name === $input->getOption('endpoint'));
        }

        $templating = $this->createTemplateEngine();

        foreach ($endpoints as $endpoint) {
            $output->writeln(sprintf('Processing endpoint "%s"', $endpoint->name));

            $facade = new EndpointClassFacade($endpoint, $input->getArgument('sources'));
            $src = $templating->render('model.php', [
                'endpoint' => new EndpointClassFacade($endpoint, $input->getArgument('sources')),
            ]);

            file_put_contents($facade->filename, "<?php\n\n$src");
            $output->writeln(sprintf('Updated/Created file "%s" for endpoint "%s"', $facade->filename, $endpoint->name));
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

    private function createTemplateEngine(): EngineInterface
    {
        $filesystemLoader = new FilesystemLoader('resources/views/%name%');
        $templating = new PhpEngine(new TemplateNameParser(), $filesystemLoader);
        $templating->set(new SlotsHelper());

        return $templating;
    }

    private function loadMetaData(): MetaData
    {
        $data = file_get_contents(self::$metaDataFile);
        $json = (array) json_decode($data, flags: JSON_THROW_ON_ERROR);

        return MetaData::fromStdClass($json);
    }
}