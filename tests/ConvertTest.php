<?php

namespace Rdh\LaravelFactoryConverterTests;

use PHPUnit\Framework\TestCase;
use Rdh\LaravelFactoryConverter\Commands\ConvertCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Process\Process;

class ConvertTest extends TestCase
{
    /**
     * @var Application
     */
    private $application;

    /**
     * @var false|string
     */
    private $pathOriginal;

    /**
     * @var false|string
     */
    private $pathResult;

    /**
     * @var false|string
     */
    private $pathExpected;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pathOriginal = \realpath(__DIR__ . '/../tests/stubs/original');
        $this->pathResult   = \realpath(__DIR__ . '/../tests/stubs/result');
        $this->pathExpected = \realpath(__DIR__ . '/../tests/stubs/expected');

        if (! $this->pathOriginal || ! $this->pathResult || ! $this->pathExpected) {
            throw new \Exception('One of the paths are incorrect');
        }

        Process::fromShellCommandline(\sprintf('rm -rf %s', $this->pathResult . '/*'))->run();
        Process::fromShellCommandline(\sprintf('cp -R %s %s', $this->pathOriginal . '/*', $this->pathResult))->run();

        $this->application = new Application();
        $this->application->add($command = new ConvertCommand());
        $this->application->setDefaultCommand($command->getName());
        $this->application->setAutoExit(false);
    }

    /** @test */
    public function canConvertFiles(): void
    {
        $commandTester = $this->runCommand();

        $this->assertEquals(0, $commandTester->getStatusCode());
        $this->assertEquals(
            \file_get_contents($this->pathResult . '/database/factories/ClientFactory.php'),
            \file_get_contents($this->pathExpected . '/database/factories/ClientFactory.php')
        );
        $this->assertFileDoesNotExist($this->pathResult . '/database/factories-old/ClientFactory.php');
    }

    /** @test */
    public function canConvertAndKeepOldFiles(): void
    {
        $commandTester = $this->runCommand(['-kof' => 1]);

        $this->assertEquals(0, $commandTester->getStatusCode());
        $this->assertFileExists($this->pathResult . '/database/factories-old/ClientFactory.php');
    }

    /** @test */
    public function canConvertWithoutDocBlocks(): void
    {
        $commandTester = $this->runCommand(['-w' => 1]);

        $this->assertEquals(0, $commandTester->getStatusCode());
        $this->assertEquals(
            \file_get_contents($this->pathResult . '/database/factories/ClientFactory.php'),
            \file_get_contents($this->pathExpected . '/database/factories/ClientFactory-without-doc-blocks.php')
        );
    }

    /** @test */
    public function cannotConvertWithExistingSyntaxErrors(): void
    {
        $path = $this->pathResult . '/database/factories/ClientFactory.php';
        \file_put_contents($path, \mb_substr(\file_get_contents($path), 0, -8));

        $commandTester = $this->runCommand();

        $this->assertGreaterThan(0, $commandTester->getStatusCode());
    }

    /** @test */
    public function cannotConvertWithMissingComposerJson(): void
    {
        \unlink($this->pathResult . '/composer.json');

        $commandTester = $this->runCommand();

        $this->assertGreaterThan(0, $commandTester->getStatusCode());
    }

    private function runCommand(array $options = []): ApplicationTester
    {
        $options = \array_merge([
            'convert',
            '-d' => $this->pathResult,
        ], $options);

        $commandTester = new ApplicationTester($this->application);
        $commandTester->run($options);

        return $commandTester;
    }
}
