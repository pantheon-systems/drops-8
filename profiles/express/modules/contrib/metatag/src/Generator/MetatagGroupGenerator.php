<?php

namespace Drupal\metatag\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\TwigRenderer;

class MetatagGroupGenerator extends Generator {

  /**
   * @var Manager
   */
  protected $extensionManager;

  /**
   * @var TwigRenderer
   */
  protected $render;

  /**
   * MetatagGroupGenerator constructor.
   *
   * @param Manager $extensionManager
   */
  public function __construct(Manager $extensionManager, TwigRenderer $render) {
    $this->extensionManager = $extensionManager;

    $render->addSkeletonDir(__DIR__ . '/../../templates/');
    $this->setRenderer($render);
  }

  /**
   * Generator plugin.
   *
   * @param string $base_class
   * @param string $module
   * @param string $label
   * @param string $description
   * @param string $plugin_id
   * @param string $class_name
   * @param string $weight
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
