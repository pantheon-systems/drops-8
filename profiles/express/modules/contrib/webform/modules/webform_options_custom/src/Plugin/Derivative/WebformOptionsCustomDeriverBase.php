<?php

namespace Drupal\webform_options_custom\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides base class for webform custom options derivers.
 */
abstract class WebformOptionsCustomDeriverBase extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The type of custom element (element or entity_reference).
   *
   * @var string
   */
  protected $type;

  /**
   * The custom options storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $optionsCustomStorage;

  /**
   * Constructs new WebformReusableCompositeDeriver.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $webform_options_custom_storage
   *   The Dynamic Composite storage.
   */
  public function __construct(ConfigEntityStorageInterface $webform_options_custom_storage) {
    $this->optionsCustomStorage = $webform_options_custom_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')->getStorage('webform_options_custom')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $webform_options_custom_entities = $this->optionsCustomStorage->loadMultiple();
    foreach ($webform_options_custom_entities as $webform_options_custom_entity) {
      if ($webform_options_custom_entity->get($this->type)) {
        $this->derivatives[$webform_options_custom_entity->id()] = $base_plugin_definition;
        $this->derivatives[$webform_options_custom_entity->id()]['label'] = $webform_options_custom_entity->label() . ($this->type === 'entity_reference' ? ' ' . $this->t('(Entity reference)') : '');
        $this->derivatives[$webform_options_custom_entity->id()]['description'] = $webform_options_custom_entity->get('description');
      }
    }
    return $this->derivatives;
  }

}
