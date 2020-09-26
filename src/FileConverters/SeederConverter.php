<?php

namespace Rdh\LaravelFactoryConverter\FileConverters;

use Symfony\Component\Finder\SplFileInfo;

class SeederConverter extends Converter
{
    public function convert(SplFileInfo $file): void
    {
        $lines = collect(\explode(PHP_EOL, $file->getContents()));

        $index = $lines->search(function ($line) {
            if (strpos($line, 'use ') === 0) {
                return true;
            }

            return strpos($line, 'class') === 0;
        });

        $lines[$index] = 'namespace Database\\Seeders;' . str_repeat(PHP_EOL, 2) . $lines[$index];

        file_put_contents($file->getPathname(), $lines->implode(PHP_EOL));
    }
}
