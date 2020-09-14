<?php

namespace Rdh\LaravelFactoryConverter;

use Symfony\Component\Finder\SplFileInfo;

class FileParser
{
    /**
     * @var string
     */
    private $contents;

    /**
     * @var string
     */
    private $model;

    /**
     * @var array
     */
    private $imports = [];

    /**
     * @var string
     */
    private $definition;

    public static function parse(SplFileInfo $file): self
    {
        return new self($file);
    }

    private function __construct(SplFileInfo $file)
    {
        $this->contents = $file->getContents();

        $this->findModel();
        $this->findImports();
        $this->findDefinition();
    }

    private function findModel(): void
    {
        $model = \preg_replace('/.*\$factory->define\((.*)::class, function.*/s', '$1', $this->contents);

        if (\mb_strpos($model, '\\') !== false) {
            $this->imports[] = 'use ' . \ltrim($model, '\\') . ';';
        }

        $this->model = \preg_replace('/.*\\\([A-Za-z]+)/', '$1', $model);
    }

    private function findImports(): void
    {
        $this->imports = \array_merge(
            $this->imports,
            \array_filter(\explode(PHP_EOL, $this->contents), function (string $line): bool {
                return \preg_match('/^ *use [A-Za-z]+/', $line) > 0
                    && \mb_strpos($line, 'Faker\Generator') === false;
            })
        );

        $this->imports[] = 'use Illuminate\Database\Eloquent\Factories\Factory;';
    }

    private function findDefinition(): void
    {
        $definition = \preg_replace('/.*\$factory.*\) ?{' . PHP_EOL . '(.*)} ?\).*/s', '$1', $this->contents);
        $definition = \explode(PHP_EOL, $definition);
        $definition = \array_map(function (string $line): string {
            if (! \trim($line)) {
                return \trim($line);
            }

            return \str_repeat(' ', 4) . $line;
        }, $definition);

        $this->definition = \str_replace('$faker', '$this->faker', \implode(PHP_EOL, $definition));
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getImports(): array
    {
        \sort($this->imports);

        return \array_unique($this->imports);
    }

    public function getDefinition(): string
    {
        return $this->definition;
    }
}
