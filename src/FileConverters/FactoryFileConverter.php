<?php declare(strict_types=1);

namespace Rdh\LaravelFactoryConverter\FileConverters;

use Rdh\LaravelFactoryConverter\Models\Factory;

class FactoryFileConverter extends Converter
{
    public function convert(Factory $factory): void
    {
        $path = $this->input->getOption('directory') . '/database/factories/' . $factory->getModelBasename() . 'Factory.php';

        $this->write($path, 'factory.php', [
            'model'           => $factory->getModelBasename(),
            'imports'         => $factory->getImports(),
            'definition'      => $factory->getDefinition(),
            'removeDocBlocks' => (bool) $this->input->getOption('without-doc-blocks'),
        ]);

        $this->format($path);
    }
}
