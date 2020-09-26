<?php

/**
 * @var \Symfony\Component\Templating\PhpEngine $view
 * @var string                                  $namespace
 * @var string                                  $model
 * @var string                                  $extends
 * @var array                                   $imports
 * @var string                                  $contents
 */

?>
<?= '<?php' ?>


namespace <?= $namespace ?>;

<?php foreach ($imports as $import) : ?>
<?= $view->escape($import) ?>

<?php endforeach; ?>

class <?= $view->escape($model) ?> extends <?= $view->escape($extends) ?>

{
    use HasFactory;
<?= $contents ?>
}
