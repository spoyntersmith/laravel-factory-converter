# Laravel Factory Converter

This package allows to easily convert your 'classic' model factories to the Laravel version 8 class style.

## Requirements

* PHP 7.4
* Your models are in the `App` namespace (`app` directory)
* There is no namespace defined in your seeders
* Your code is PSR-2 (ish) formatted

## Installation

```
composer global require rdh/laravel-factory-converter:dev-master
```

## Usage

Make sure that `~/.composer/vendor/bin` is in your `$PATH`. Run the following commands for your project:

```
cd your-project
laravel-factories-converter
```

## Options

Check `laravel-factory-converter --help` for all options

## To do

- [ ] Transform seeds (add namespace)
