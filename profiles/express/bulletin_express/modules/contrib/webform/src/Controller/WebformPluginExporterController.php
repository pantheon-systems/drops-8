<?php

namespace Drupal\webform\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for all results exporters.
 */
class WebformPluginExporterController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * A results exporter plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * Constructs a WebformPluginExporterController object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   *   A results exporter plugin manager.
   */
  public function __construct(PluginManagerInterface $plugin_manager) {
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.webform.exporter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function index() {
    $definitions = $this->pluginManager->getDefinitions();
    $definitions = $this->pluginManager->getSortedDefinitions($definitions);

    $rows = [];
    foreach ($definitions as $plugin_id => $definition) {
      $rows[$plugin_id] = [
        $plugin_id,
        $definition['label'],
        $definition['description'],
        $definition['provider'],
      ];
    }

    ksort($rows);
    return [
      '#type' => 'table',
      '#header' => [
        $this->t('ID'),
        $this->t('Label'),
        $this->t('Description'),
        $this->t('Provided by'),
      ],
      '#rows' => $rows,
      '#sticky' => TRUE,
    ];
  }

}
