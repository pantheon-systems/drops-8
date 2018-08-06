<?php

namespace Drupal\entity_embed\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FormatterPluginManager;

/**
 * Provides Entity Embed Display plugin definitions for field formatters.
 *
 * @see \Drupal\entity_embed\FieldFormatterEntityEmbedDisplayBase
 */
class FieldFormatterDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The manager for formatter plugins.
   *
   * @var \Drupal\Core\Field\FormatterPluginManager.
   */
  protected $formatterManager;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs new FieldFormatterEntityEmbedDisplayBase.
   *
   * @param \Drupal\Core\Field\FormatterPluginManager $formatter_manager
   *   The field formatter plugin manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   */
  public function __construct(FormatterPluginManager $formatter_manager, ConfigFactoryInterface $config_factory) {
    $this->formatterManager = $formatter_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('plugin.manager.field.formatter'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \LogicException
   *   Throws an exception if field type is not defined in the annotation of the
   *   Entity Embed Display plugin.
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // The field type must be defined in the annotation of the Entity Embed
    // Display plugin.
    if (!isset($base_plugin_definition['field_type'])) {
      throw new \LogicException("Undefined field_type definition in plugin {$base_plugin_definition['id']}.");
    }
    $mode = $this->configFactory->get('entity_embed.settings')->get('rendered_entity_mode');
    foreach ($this->formatterManager->getOptions($base_plugin_definition['field_type']) as $formatter => $label) {
      $this->derivatives[$formatter] = $base_plugin_definition;
      $this->derivatives[$formatter]['label'] = $label;
      // Don't show entity_reference_entity_view in the UI if the rendered
      // entity mode is FALSE. In that case we show view modes from
      // ViewModeDeriver, entity_reference_entity_view is kept for backwards
      // compatibility.
      if ($formatter == 'entity_reference_entity_view' && $mode == FALSE) {
        $this->derivatives[$formatter]['no_ui'] = TRUE;
      }
    }
    return $this->derivatives;
  }

}
