<?php declare(strict_types=1);

namespace Rdh\LaravelFactoryConverterTests;

use PHPUnit\Framework\TestCase;
use Rdh\LaravelFactoryConverter\Commands\ConvertCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Process\Process;

class ConvertTest extends TestCase
{
    private Application $application;
    private string $pathOriginal;
    private string $pathActual;
    private string $pathExpected;

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
        $this->assertFileContentsEquals('/composer.json');
        $this->assertFileContentsEquals('/database/factories/PostFactory.php');
        $this->assertFileContentsEquals('/database/factories/UserFactory.php');
        $this->assertFileContentsEquals('/app/Models/Post.php');
        $this->assertFileContentsEquals('/app/Models/User.php');
        $this->assertFileContentsEquals('/database/seeders/DatabaseSeeder.php');
        $this->assertFileContentsEquals('/tests/ExampleClass.php');
        $this->assertFileDoesNotExist($this->pathActual . '/database/old-factories');
    }

    /** @test */
    public function canConvertWithoutDocBlocks(): void
    {
        $commandTester = $this->runCommand(['-w' => 1]);

        $this->assertEquals(0, $commandTester->getStatusCode());
        $this->assertFileContentsEquals('/database/factories/UserFactory-without-doc-blocks.php', '/database/factories/UserFactory.php');
    }

    /** @test */
    public function cannotConvertWithMissingComposerJson(): void
    {
        \unlink($this->pathActual . '/composer.json');

        $commandTester = $this->runCommand();

        $this->assertGreaterThan(0, $commandTester->getStatusCode());
    }

    private function assertFileContentsEquals(string $fileOne, string $fileTwo = null): void
    {
        $this->assertEquals(
            \file_get_contents($this->pathExpected . $fileOne),
            \file_get_contents($this->pathActual . ($fileTwo ?: $fileOne))
        );
    }

    private function runCommand(array $options = []): ApplicationTester
    {
        $options = \array_merge([
            'convert',
            '-d' => $this->pathActual,
            '-a' => true,
        ], $options);

        $commandTester = new ApplicationTester($this->application);
        $commandTester->run($options);

        return $commandTester;
    }
}
