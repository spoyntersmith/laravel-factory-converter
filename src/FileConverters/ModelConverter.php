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
        $contents = \file_get_contents($path);

        $this->write($path, 'model.php', [
            'namespace' => $factory->getModelNamespace(),
            'model'     => $factory->getModelBasename(),
            'extends'   => $this->findExtends($contents),
            'imports'   => $this->findImports($contents),
            'contents'  => $this->findContents($contents),
        ]);

        $this->format($path);
    }

    private function findExtends(string $contents): string
    {
        return \preg_replace('/.*class[A-Za-z \n\r]+extends ([A-Za-z\\\]+).*/s', '$1', $contents);
    }

    private function findImports(string $contents): array
    {
        $imports = \preg_replace('/^<\?php.*namespace[ A-Za-z\\\]+;\s+(.*)class.*/s', '$1', $contents);

        return collect(\explode(PHP_EOL, $imports))
            ->filter()
            ->merge(['use Illuminate\Database\Eloquent\Factories\HasFactory;'])
            ->sort()
            ->toArray();
    }

    private function findContents(string $contents): string
    {
        $contents = \preg_replace('/.*\{' . PHP_EOL . '?(.*)' . PHP_EOL . '?}/s', '$1', $contents);
        $contents = \trim($contents, PHP_EOL);

        return $contents . PHP_EOL;
    }
}
