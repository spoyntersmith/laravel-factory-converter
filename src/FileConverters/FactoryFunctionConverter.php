<?php declare(strict_types=1);

namespace Rdh\LaravelFactoryConverter\FileConverters;

use Symfony\Component\Finder\SplFileInfo;

class FactoryFunctionConverter extends Converter
{
    public function convert(SplFileInfo $file): void
    {
        $contents = preg_replace('/(.*)factory\(([A-Za-z\\\]+)::class\)(.*)/', '$1$2::factory()$3', $file->getContents());

        file_put_contents($file->getPathname(), $contents);

        $this->format($file->getPathname());
    }
}
