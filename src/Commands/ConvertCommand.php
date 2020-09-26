<?php declare(strict_types=1);

namespace Rdh\LaravelFactoryConverter\Commands;

use Rdh\LaravelFactoryConverter\Exceptions\ComposerJsonNotFoundException;
use Rdh\LaravelFactoryConverter\Exceptions\FilesNotMovedException;
use Rdh\LaravelFactoryConverter\FileConverters\FactoryFileConverter;
use Rdh\LaravelFactoryConverter\FileConverters\ModelConverter;
use Rdh\LaravelFactoryConverter\FileConverters\SeederConverter;
use Rdh\LaravelFactoryConverter\Models\Factory;
use Rdh\LaravelFactoryConverter\Process;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Templating\Helper\SlotsHelper;
use Symfony\Component\Templating\Loader\FilesystemLoader;
use Symfony\Component\Templating\PhpEngine;
use Symfony\Component\Templating\TemplateNameParser;

class ConvertCommand extends Command
{
    private PhpEngine $templateEngine;
    private FactoryFileConverter $factoryFileConverter;
    private ModelConverter $modelConverter;
    private SeederConverter $seederConverter;
    private OutputInterface $output;
    private string $directory;
    private string $directoryOldFactories;

    public function __construct()
    {
        parent::__construct();

        $filesystemLoader     = new FilesystemLoader(__DIR__ . '/../../resources/views/%name%');
        $this->templateEngine = new PhpEngine(new TemplateNameParser(), $filesystemLoader);
        $this->templateEngine->set(new SlotsHelper());
    }

    protected function configure()
    {
        $this
            ->setName('convert')
            ->addOption('directory', '-d', InputOption::VALUE_OPTIONAL, 'Change the working directory', \getcwd())
            ->addOption('without-doc-blocks', '-w', InputOption::VALUE_NONE, 'Without the doc blocks');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->factoryFileConverter     = new FactoryFileConverter($input, $this->templateEngine);
        $this->modelConverter           = new ModelConverter($input, $this->templateEngine);
        $this->seederConverter          = new SeederConverter($input, $this->templateEngine);

        $this->output                = $output;
        $this->directory             = (string) $input->getOption('directory');
        $this->directoryOldFactories = \str_replace('//', '/', $this->directory . '/old-factories');

        $this->updateComposerJson();
        $this->moveFiles();
        $this->convertFactoriesAndModels();
        $this->convertFactoryFunctions();
        $this->convertSeeders();

        return 0;
    }

    protected function updateComposerJson(): void
    {
        $this->output->writeln('1. Updating composer.json');

        $path = $this->directory . '/composer.json';

        if (! \file_exists($path)) {
            throw new ComposerJsonNotFoundException('composer.json could not be found');
        }

        $configuration = \json_decode(\file_get_contents($path), true);
        $keyFactories  = \array_search('database/factories', $configuration['autoload']['classmap'] ?? []);
        $keySeeders    = \array_search('database/seeders', $configuration['autoload']['classmap'] ?? []);

        if ($keyFactories !== false) {
            unset($configuration['autoload']['classmap'][$keyFactories]);
        }

        if ($keySeeders !== false) {
            unset($configuration['autoload']['classmap'][$keySeeders]);
        }

        if (\count($configuration['autoload']['classmap']) === 0) {
            unset($configuration['autoload']['classmap']);
        }

        if (\count($configuration['autoload']) === 0) {
            unset($configuration['autoload']);
        }

        $configuration['autoload']['psr-4']['Database\\Factories\\'] = 'database/factories/';
        $configuration['autoload']['psr-4']['Database\\Seeder\\'] = 'database/seeders/';

        \file_put_contents($path, \str_replace('\/', '/', \json_encode($configuration, JSON_PRETTY_PRINT)));
    }

    protected function moveFiles(): void
    {
        $this->output->writeLn(\sprintf('2. Moving files from %s to %s', $this->directory . '/database/factories', $this->directoryOldFactories));

        Process::run(\sprintf('mkdir %s', $this->directoryOldFactories));

        $process = Process::run(\sprintf(
            'mv %s %s',
            $this->directory . '/database/factories/*',
            $this->directoryOldFactories,
        ));

        if (! $process->isSuccessful()) {
            throw new FilesNotMovedException('Files were not moved before converting');
        }

        Process::run(\sprintf('mkdir -p %s', $this->directory . '/database/factories/'));
    }

    protected function convertFactoriesAndModels(): void
    {
        $this->output->writeLn(\sprintf('3. Converting files from %s to %s', $this->directoryOldFactories, $this->directory . '/database/factories'));

        foreach ($this->files($this->directoryOldFactories) as $file) {
            $this->convertFactoryAndModel($file);
        }

        $this->output->writeLn('4. Deleting old factories');

        Process::run(\sprintf('rm -rf %s', $this->directoryOldFactories));
    }

    protected function convertFactoryAndModel(SplFileInfo $file): void
    {
        $this->output->writeLn(\sprintf('Converting file: %s', $file->getFilename()));

        $file = Factory::fromFile($file);

        $this->factoryFileConverter->convert($file);
        $this->modelConverter->convert($file);
    }

    protected function convertFactoryFunctions()
    {
        $this->output->writeLn('5. Converting factory functions');

        $finder = (new Finder())
            ->in([
                $this->directory . '/app',
                $this->directory . '/database',
                $this->directory . '/tests',
            ])
            ->name('*.php')
            ->contains('factory(');

        foreach ($finder as $file) {
            $contents = preg_replace('/(.*)factory\(([A-Za-z\\\]+)::class\)(.*)/', '$1$2::factory()$3', $file->getContents());

            file_put_contents($file->getPathname(), $contents);
        }
    }

    protected function convertSeeders(): void
    {
        $this->output->writeLn('6. Converting seeders');

        Process::run(\sprintf('mv %s %s', $this->directory . '/database/seeds', $this->directory . '/database/seeders'));

        foreach ($this->files($this->directory . '/database/seeders') as $file) {
            $this->seederConverter->convert($file);
        }
    }

    protected function files(string $in): Finder
    {
        return $finder = (new Finder())->in($in)->name('*.php');
    }
}
