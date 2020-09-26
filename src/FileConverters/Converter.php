<?php declare(strict_types=1);

namespace Rdh\LaravelFactoryConverter\FileConverters;

use Rdh\LaravelFactoryConverter\Process;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Templating\PhpEngine;

abstract class Converter
{
    protected InputInterface $input;
    protected PhpEngine $templateEngine;

    public function __construct(InputInterface $input, PhpEngine $templateEngine)
    {
        $this->input          = $input;
        $this->templateEngine = $templateEngine;
    }

    protected function write(string $destination, string $template, array $data = []): void
    {
        $result = \file_put_contents($destination, $this->templateEngine->render($template, $data));

        if (! $result) {
            throw new \Exception('File not written: ' . $destination);
        }
    }

    protected function format(string $file): void
    {
        Process::run(sprintf(
            'cd %s && php vendor/bin/php-cs-fixer fix %s --rules=@PSR2',
            $this->input->getOption('directory'),
            $file
        ));
    }
}
