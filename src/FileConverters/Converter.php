<?php

namespace Rdh\LaravelFactoryConverter\FileConverters;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Templating\PhpEngine;

abstract class Converter
{
    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var PhpEngine
     */
    protected $templateEngine;

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
}
