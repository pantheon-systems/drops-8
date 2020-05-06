<?php

namespace Drupal\webform\Plugin\Field\FieldFormatter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\webform\Plugin\Field\FieldType\WebformEntityReferenceItem;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for 'Webform Entity Reference formatter' plugin implementations.
 */
abstract class WebformEntityReferenceFormatterBase extends EntityReferenceFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * WebformEntityReferenceLinkFormatter constructor.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, RendererInterface $renderer, ConfigFactoryInterface $config_factory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->configFactory = $config_factory;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('renderer'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntitiesToView(EntityReferenceFieldItemListInterface $items, $langcode) {
    /** @var \Drupal\webform\WebformInterface[] $entities */
    $entities = parent::getEntitiesToView($items, $langcode);
    foreach ($entities as $entity) {
      /** @var \Drupal\webform\Plugin\Field\FieldType\WebformEntityReferenceItem $item */
      $item = $entity->_referringItem;

      // Only override an open webform.
      if ($entity->isOpen()) {
        // Directly call set override to prevent the altered webform from being
        // saved.
        $entity->setOverride();
        $entity->set('open', $item->open);
        $entity->set('close', $item->close);
        $entity->setStatus($item->status);
      }
    }
    return $entities;
  }

  /**
   * Set cache context.
   *
   * @param array $elements
   *   The elements that need cache context.
   * @param \Drupal\webform\WebformInterface|null $webform
   *   The webform entity reference webform.
   * @param \Drupal\webform\Plugin\Field\FieldType\WebformEntityReferenceItem $item
   *   The webform entity reference item.
   */
  protected function setCacheContext(array &$elements, WebformInterface $webform, WebformEntityReferenceItem $item) {
    // Track if webform.settings is updated.
    $config = $this->configFactory->get('webform.settings');
    $this->renderer->addCacheableDependency($elements, $config);

    // Track if the webform is updated.
    $this->renderer->addCacheableDependency($elements, $webform);

    // Calculate the max-age based on the open/close data/time for the item
    // and webform.
    $max_age = 0;
    $states = ['open', 'close'];
    foreach ($states as $state) {
      if ($item->status === WebformInterface::STATUS_SCHEDULED) {
        $item_state = $item->$state;
        if ($item_state && strtotime($item_state) > time()) {
          $item_seconds = strtotime($item_state) - time();
          if (!$max_age && $item_seconds > $max_age) {
            $max_age = $item_seconds;
          }
        }
      }
      if ($webform->status() === WebformInterface::STATUS_SCHEDULED) {
        $webform_state = $webform->get($state);
        if ($webform_state && strtotime($webform_state) > time()) {
          $webform_seconds = strtotime($webform_state) - time();
          if (!$max_age && $webform_seconds > $max_age) {
            $max_age = $webform_seconds;
          }
        }
      }
    }

    if ($max_age) {
      $elements['#cache']['max-age'] = $max_age;
    }
  }

}
