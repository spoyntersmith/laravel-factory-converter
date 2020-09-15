# Laravel Factory Converter

This package allows to easily convert your 'classic' model factories to the Laravel version 8 class style.

## Requirements

* PHP 7.3

## Installation

```
composer global require rdh/laravel-factory-converter:dev-master
```

## Usage

Make sure that `~/.composer/vendor/bin` is in your `$PATH`. Run the following command from within your project:

```
cd your-project
laravel-factories-converter
```

## Options

Check `laravel-factory-converter --help` for all options

## To do

- [ ] Add traits to models
- [ ] Replace factories in tests (or other files like seeds)
- [ ] Transform seeds (add namespace)
