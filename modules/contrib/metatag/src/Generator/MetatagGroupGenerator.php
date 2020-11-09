<?php

namespace Drupal\metatag\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\TwigRenderer;

/**
 * Drupal Console plugin for generating a group.
 */
class MetatagGroupGenerator extends Generator {

  /**
   * The console manager.
   *
   * @var \Drupal\Console\Extension\Manager
   */
  protected $extensionManager;

  /**
   * The twig renderer.
   *
   * @var \Drupal\Console\Core\Utils\TwigRenderer
   */
  protected $renderer;

  /**
   * MetatagGroupGenerator constructor.
   *
   * @param \Drupal\Console\Extension\Manager $extensionManager
   *   An extension manager.
   * @param \Drupal\Console\Core\Utils\TwigRenderer $renderer
   *   Twig renderer.
   */
  public function __construct(Manager $extensionManager, TwigRenderer $renderer) {
    $this->extensionManager = $extensionManager;

    $renderer->addSkeletonDir(__DIR__ . '/../../templates/');
    $this->setRenderer($renderer);
  }

  /**
   * Generator plugin.
   *
   * @param string $base_class
   *   Base class.
   * @param string $module
   *   Module name.
   * @param string $label
   *   Group label.
   * @param string $description
   *   Group description.
   * @param string $plugin_id
   *   Plugin ID.
   * @param string $class_name
   *   Class name.
   * @param string $weight
   *   Group weight.
   */
  public function generate($base_class, $module, $label, $description, $plugin_id, $class_name, $weight) {
    $parameters = [
      'base_class' => $base_class,
      'module' => $module,
      'label' => $label,
      'description' => $description,
      'plugin_id' => $plugin_id,
      'class_name' => $class_name,
      'weight' => $weight,
      'prefix' => '<' . '?php',
    ];

    $this->renderFile(
      'group.php.twig',
      $this->extensionManager->getPluginPath($module, 'metatag/Group') . '/' . $class_name . '.php',
      $parameters
    );
  }

}
