<?php

namespace Rdh\LaravelFactoryConverter\Commands;

use Rdh\LaravelFactoryConverter\Exceptions\ComposerJsonNotFoundException;
use Rdh\LaravelFactoryConverter\Exceptions\FilesNotMovedException;
use Rdh\LaravelFactoryConverter\Exceptions\FileSyntaxException;
use Rdh\LaravelFactoryConverter\FileParser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Process\Process;
use Symfony\Component\Templating\Helper\SlotsHelper;
use Symfony\Component\Templating\Loader\FilesystemLoader;
use Symfony\Component\Templating\PhpEngine;
use Symfony\Component\Templating\TemplateNameParser;

class ConvertCommand extends Command
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var string
     */
    private $directory;

    /**
     * @var bool
     */
    private $keepOldFactories;

    /**
     * @var string
     */
    private $directoryOldFactories;

    /**
     * @var bool
     */
    private $withoutDocBlocks;

    protected function configure()
    {
        $this
            ->setName('convert')
            ->addOption('directory', '-d', InputOption::VALUE_OPTIONAL, 'Change the working directory', \getcwd())
            ->addOption('keep-old-factories', '-kof', InputOption::VALUE_NONE, 'Keep the old factory files in a separate directory')
            ->addOption('directory-old-factories', '-dof', InputOption::VALUE_OPTIONAL, 'Keep the old factory files in a separate directory', 'database/factories-old')
            ->addOption('without-doc-blocks', '-w', InputOption::VALUE_NONE, 'Without the doc blocks');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output                = $output;
        $this->directory             = $input->getOption('directory');
        $this->keepOldFactories      = $input->getOption('keep-old-factories');
        $this->directoryOldFactories = \str_replace('//', '/', $this->directory . '/' . $input->getOption('directory-old-factories'));
        $this->withoutDocBlocks      = $input->getOption('without-doc-blocks');

        $this->checkForSyntaxErrors();
        $this->updateComposerJson();
        $this->moveFiles();

        $this->output->writeLn(\sprintf('4. Converting files from %s to %s', $this->directoryOldFactories, $this->directory . '/database/factories'));

        foreach ($this->files($this->directoryOldFactories) as $file) {
            $this->convertFile($file);
        }

        if (! $this->keepOldFactories) {
            $this->output->writeLn('5. Deleting old factories');

            $this->runCommand(\sprintf('rm -rf %s', $this->directoryOldFactories));
        }

        return 0;
    }

    private function checkForSyntaxErrors(): void
    {
        $this->output->writeln('1. Checking files for syntax errors');

        foreach ($this->files() as $file) {
            $process = $this->runCommand(\sprintf('php -l %s', $file->getPathname()));

            if (! ($process->isSuccessful())) {
                throw new FileSyntaxException(\sprintf("There is a syntax error in '%'", $file->getPathname()));
            }
        }
    }

    private function updateComposerJson(): void
    {
        $this->output->writeln('2. Updating composer.json');

        $path = $this->directory . '/composer.json';

        if (! \file_exists($path)) {
            throw new ComposerJsonNotFoundException('composer.json could not be found');
        }

        $configuration = \json_decode(\file_get_contents($path), true);
        $key           = \array_search('database/factories', $configuration['autoload']['classmap'] ?? []);

        if ($key !== false) {
            unset($configuration['autoload']['classmap'][$key]);
        }

        $configuration['autoload']['psr-4']['Database\\Factories\\'] = 'database/factories/';

        \file_put_contents($path, \str_replace('\/', '/', \json_encode($configuration, JSON_PRETTY_PRINT)));
    }

    private function moveFiles(): void
    {
        $this->output->writeLn(\sprintf('3. Moving files from %s to %s', $this->directory . '/database/factories', $this->directoryOldFactories));

        $this->runCommand(\sprintf('mkdir %s', $this->directoryOldFactories));

        $process = $this->runCommand(\sprintf(
            'mv %s %s',
            $this->directory . '/database/factories/*',
            $this->directoryOldFactories,
        ));

        if (! $process->isSuccessful()) {
            throw new FilesNotMovedException('Files were not moved before converting. Nothing should have changed though.');
        }

        $this->runCommand(\sprintf('mkdir -p %s', $this->directory . '/database/factories/'));
    }

    private function convertFile(SplFileInfo $file): void
    {
        $this->output->writeLn(\sprintf('Converting file: %s', $file->getFilename()));

        $file  = FileParser::parse($file);
        $model = $file->getModel();
        $path  = $this->directory . '/database/factories/' . $model . 'Factory.php';

        $result = \file_put_contents($path, $this->render('factory.php', [
            'model'           => $model,
            'imports'         => $file->getImports(),
            'definition'      => $file->getDefinition(),
            'removeDocBlocks' => $this->withoutDocBlocks,
        ]));

        if (! $result) {
            throw new \Exception('File not written: ' . $path);
        }
    }

    private function files(string $in = null): Finder
    {
        return $finder = (new Finder())->in($in ?: $this->directory . '/database/factories')->name('*.php');
    }

    private function runCommand(string $command): Process
    {
        $process = Process::fromShellCommandline($command);
        $process->run();

        return $process;
    }

    private function render(string $template, array $data = []): string
    {
        $filesystemLoader = new FilesystemLoader(__DIR__ . '/../../resources/views/%name%');
        $templating       = new PhpEngine(new TemplateNameParser(), $filesystemLoader);
        $templating->set(new SlotsHelper());

        return \str_replace(PHP_EOL . PHP_EOL . PHP_EOL, PHP_EOL . PHP_EOL, $templating->render($template, $data));
    }
}
