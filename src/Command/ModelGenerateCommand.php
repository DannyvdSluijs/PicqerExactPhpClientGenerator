<?php

declare(strict_types=1);

namespace PicqerExactPhpClientGenerator\Command;

use MetaDataTool\Command\MetaDataBuilderCommand;
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

class ModelGenerateCommand extends Command
{
    protected static string $defaultName = 'run';
    protected static string $metaDataFile = '.meta/meta-data.json';

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

        foreach ($json as $endpoint) {
           $file = sprintf(
               '%s/src/Picqer/Financials/Exact/%s.php',
               $input->getArgument('sources'),
               substr($endpoint->endpoint, 0, -1)
           );

           if (!is_readable($file)) {
               $output->writeln(sprintf('Unable to find file "%s" for endpoint "%s"', $file, $endpoint->endpoint));
               continue;
           }

           $endpoint->properties = array_filter($endpoint->properties, static function ($p) { return strtolower($p->description) !== 'obsolete'; });
           foreach ($endpoint->properties as $key => $property) {
               if (str_starts_with($property->description, 'Collection')) {
                   $endpoint->properties[$key]->type = substr($property->type, 0, -1) . '[]';
                   continue;
               }
               $endpoint->properties[$key]->type = match ($property->type) {
                   'Edm.Int32', 'Edm.Int16', 'Edm.Byte' => 'int',
                   'Edm.Double' => 'float',
                   'Edm.Boolean' => 'bool',
                   default => 'string'
               };
           }

            $src = $templating->render('model.php', ['endpoint' => $endpoint]);

            file_put_contents($file, "<?php\n\n$src");
            $output->writeln(sprintf('Updated file "%s" for endpoint "%s"', $file, $endpoint->endpoint));
        }

        return 0;
    }
}