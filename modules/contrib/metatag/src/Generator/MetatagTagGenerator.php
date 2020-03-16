<?php

namespace Drupal\metatag\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\TwigRenderer;

/**
 * Drupal Console plugin for generating a tag.
 */
class MetatagTagGenerator extends Generator {

  /**
   * An extension manager.
   *
   * @var \Drupal\Console\Extension\Manager
   */
  protected $extensionManager;

  /**
   * The twig renderer.
   *
   * @var \Drupal\Console\Core\Utils\TwigRenderer
   */
  protected $render;

  /**
   * MetatagTagGenerator constructor.
   *
   * @param \Drupal\Console\Extension\Manager $extensionManager
   *   An extension manager.
   * @param \Drupal\Console\Core\Utils\TwigRenderer $render
   *   Twig renderer.
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
   *   Base class.
   * @param string $module
   *   Module name.
   * @param string $name
   *   Tag name.
   * @param string $label
   *   Tag label.
   * @param string $description
   *   Tag description.
   * @param string $plugin_id
   *   Plugin ID.
   * @param string $class_name
   *   Class name.
   * @param string $group
   *   Tag group.
   * @param string $weight
   *   Tag weight.
   * @param string $type
   *   Tag type.
   * @param bool $secure
   *   Is secure.
   * @param bool $multiple
   *   Is multiple.
   */
  public function generate($base_class, $module, $name, $label, $description, $plugin_id, $class_name, $group, $weight, $type, $secure, $multiple) {
    $parameters = [
      'base_class' => $base_class,
      'module' => $module,
      'name' => $name,
      'label' => $label,
      'description' => $description,
      'plugin_id' => $plugin_id,
      'class_name' => $class_name,
      'group' => $group,
      'weight' => $weight,
      'type' => $type,
      'secure' => $secure,
      'multiple' => $multiple,
      'prefix' => '<' . '?php',
    ];

    $this->renderFile(
      'tag.php.twig',
      $this->extensionManager->getPluginPath($module, 'metatag/Tag') . '/' . $class_name . '.php',
      $parameters
    );

    $this->renderFile(
      'metatag_tag.schema.yml.twig',
      $this->extensionManager->getModule($module)->getPath() . '/config/schema/' . $module . '.metatag_tag.schema.yml',
      $parameters,
      FILE_APPEND
    );
  }

}
