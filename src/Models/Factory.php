<?php declare(strict_types=1);

namespace Rdh\LaravelFactoryConverter\Models;

use Illuminate\Support\Collection;
use Symfony\Component\Finder\SplFileInfo;

class Factory
{
    private string $contents;
    private string $model;
    private Collection $imports;
    private string $definition;

    public static function fromFile(SplFileInfo $file): self
    {
        return new self($file);
    }

    private function __construct(SplFileInfo $file)
    {
        $this->contents = $file->getContents();
        $this->imports  = collect();

        $this->findModel();
        $this->findImports();
        $this->findDefinition();
    }

    private function findModel(): void
    {
        $this->model = \ltrim(\preg_replace('/.*\$factory->define\((.*)::class, function.*/s', '$1', $this->contents), '\\');

        if (\mb_strpos($this->model, '\\') !== false) {
            $this->imports->push('use ' . $this->model . ';');
        }
    }

    private function findImports(): void
    {
        $this->imports = $this->imports
            ->merge(\array_filter(\explode(PHP_EOL, $this->contents), function (string $line): bool {
                return \preg_match('/^ *use [A-Za-z]+/', $line) > 0 && \mb_strpos($line, 'Faker\Generator') === false;
            }))
            ->merge(['use Illuminate\Database\Eloquent\Factories\Factory;']);
    }

    private function findDefinition(): void
    {
        $definition = \preg_replace('/.*\$factory.*\) ?{' . PHP_EOL . '(.*)} ?\).*/s', '$1', $this->contents);

        $this->definition = collect(\explode(PHP_EOL, $definition))
            ->map(function (string $line): string {
                if (! \trim($line)) {
                    return \trim($line);
                }

                return \str_repeat(' ', 4) . \str_replace('$faker', '$this->faker', $line);
            })
            ->implode(PHP_EOL);
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getModelNamespace(): string
    {
        return substr($this->model, 0, strrpos($this->model, '\\'));
    }

    public function getModelBasename(): string
    {
        return \preg_replace('/.*\\\([A-Za-z]+)/', '$1', $this->model);
    }

    public function getImports(): array
    {
        return $this->imports
            ->unique()
            ->sort()
            ->toArray();
    }

    public function getDefinition(): string
    {
        return $this->definition;
    }
}
