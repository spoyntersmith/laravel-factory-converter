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
    private $pathActual;

    /**
     * @var false|string
     */
    private $pathExpected;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pathOriginal = \realpath(__DIR__ . '/../tests/playground/original');
        $this->pathActual   = \realpath(__DIR__ . '/../tests/playground/actual');
        $this->pathExpected = \realpath(__DIR__ . '/../tests/playground/expected');

        if (! $this->pathOriginal || ! $this->pathActual || ! $this->pathExpected) {
            throw new \Exception('One of the paths is incorrect');
        }

        Process::fromShellCommandline(\sprintf('rm -rf %s', $this->pathActual . '/*'))->run();
        Process::fromShellCommandline(\sprintf('cp -R %s %s', $this->pathOriginal . '/*', $this->pathActual))->run();

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
            \file_get_contents($this->pathExpected . '/database/factories/UserFactory.php'),
            \file_get_contents($this->pathActual . '/database/factories/UserFactory.php')
        );
        $this->assertEquals(
            \file_get_contents($this->pathExpected . '/app/Models/User.php'),
            \file_get_contents($this->pathActual . '/app/Models/User.php')
        );
        $this->assertEquals(
            \file_get_contents($this->pathExpected . '/database/seeders/DatabaseSeeder.php'),
            \file_get_contents($this->pathActual . '/database/seeders/DatabaseSeeder.php')
        );
        $this->assertEquals(
            \file_get_contents($this->pathExpected . '/tests/ExampleClass.php'),
            \file_get_contents($this->pathActual . '/tests/ExampleClass.php')
        );
        $this->assertFileDoesNotExist($this->pathActual . '/database/old-factories');
    }

    /** @test */
    public function canConvertWithoutDocBlocks(): void
    {
        $commandTester = $this->runCommand(['-w' => 1]);

        $this->assertEquals(0, $commandTester->getStatusCode());
        $this->assertEquals(
            \file_get_contents($this->pathActual . '/database/factories/UserFactory.php'),
            \file_get_contents($this->pathExpected . '/database/factories/UserFactory-without-doc-blocks.php')
        );
    }

    /** @test */
    public function cannotConvertWithMissingComposerJson(): void
    {
        \unlink($this->pathActual . '/composer.json');

        $commandTester = $this->runCommand();

        $this->assertGreaterThan(0, $commandTester->getStatusCode());
    }

    private function runCommand(array $options = []): ApplicationTester
    {
        $options = \array_merge([
            'convert',
            '-d' => $this->pathActual,
        ], $options);

        $commandTester = new ApplicationTester($this->application);
        $commandTester->run($options);

        return $commandTester;
    }
}
