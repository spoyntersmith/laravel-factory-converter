<?php declare(strict_types=1);

namespace Rdh\LaravelFactoryConverter\FileConverters;

use Rdh\LaravelFactoryConverter\Models\Factory;

class ModelConverter extends Converter
{
    public function convert(Factory $factory): void
    {
        $path = $this->input->getOption('directory')
            .  '/' . \str_replace(['\\', 'App/'], ['/', 'app/'], $factory->getModel())
            . '.php';

        $this->format($path);

        $contents = \file_get_contents($path);
        $contents = $this->addImport($contents);
        $contents = $this->addTrait($contents);

        \file_put_contents($path, $contents);

        $this->format($path);
    }

    private function addImport(string $contents): string
    {
        $lines = collect(\explode(PHP_EOL, $contents));
        $index = $lines->search(function ($line) {
            if (strpos($line, 'use ') === 0) {
                return true;
            }

            return strpos($line, 'class') === 0;
        });

        $lines[$index] = $lines[$index] . PHP_EOL . 'use Illuminate\Database\Eloquent\Factories\HasFactory;';

        return $lines->implode(PHP_EOL);
    }

    private function addTrait(string $contents): string
    {
        return \preg_replace(
            '/(.*)(class .*{)(.*)/sU',
            '$1$2' . PHP_EOL . '    use HasFactory;' . PHP_EOL . '$3',
            $contents
        );
    }
}
