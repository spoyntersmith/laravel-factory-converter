<?php

/**
 * @var \Symfony\Component\Templating\PhpEngine $view
 * @var string                                  $model
 * @var array                                   $imports
 * @var string                                  $definition
 * @var bool                                    $removeDocBlocks
 */

?>
<?= '<?php' ?>


namespace Database\Factories;

<?php foreach ($imports as $import) : ?>
<?= $view->escape($import) ?>

<?php endforeach; ?>

class <?= $view->escape($model) ?>Factory extends Factory
{
<?php if (! $removeDocBlocks) : ?>
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
<?php endif; ?>
    protected $model = <?= $view->escape($model) ?>::class;

<?php if (! $removeDocBlocks) : ?>
    /**
     * Define the model's default state.
     *
     * @return array
     */
<?php endif; ?>
    public function definition()
    {
<?= $definition ?>
    }
}
