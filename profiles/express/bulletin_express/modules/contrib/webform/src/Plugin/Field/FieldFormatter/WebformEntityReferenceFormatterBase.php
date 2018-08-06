<?php

namespace Drupal\webform\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\webform\Plugin\Field\FieldType\WebformEntityReferenceItem;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformMessageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for 'Webform Entity Reference formatter' plugin implementations.
 */
abstract class WebformEntityReferenceFormatterBase extends EntityReferenceFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The message manager.
   *
   * @var \Drupal\webform\WebformMessageManagerInterface
   */
  protected $messageManager;

  /**
   * WebformEntityReferenceEntityFormatter constructor.
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
   * @param \Drupal\webform\WebformMessageManagerInterface $message_manager
   *   The message manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, WebformMessageManagerInterface $message_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->messageManager = $message_manager;
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
      $container->get('webform.message_manager')
    );
  }

  /**
   * Returns the webform opened status indicator.
   *
   * @param \Drupal\webform\WebformInterface|null $webform
   *   The webform entity reference webform.
   * @param \Drupal\webform\Plugin\Field\FieldType\WebformEntityReferenceItem $item
   *   The webform entity reference item.
   *
   * @return bool
   *   TRUE if the webform is open to new submissions.
   *
   * @see \Drupal\webform\WebformInterface::isOpen
   * @see \Drupal\webform\entity\Webform::isOpen
   */
  protected function isOpen(WebformInterface $webform = NULL, WebformEntityReferenceItem $item) {
    // Make sure the webform exists.
    if (!$webform) {
      return FALSE;
    }

    // If the webform is closed, all instances of the webform must be closed.
    if (!$webform->isOpen()) {
      return FALSE;
    }

    switch ($item->status) {
      case WebformInterface::STATUS_OPEN:
        return TRUE;

      case WebformInterface::STATUS_CLOSED:
        return FALSE;

      case WebformInterface::STATUS_SCHEDULED:
        $is_opened = TRUE;
        if ($item->open && strtotime($item->open) > time()) {
          $is_opened = FALSE;
        }

        $is_closed = FALSE;
        if ($item->close && strtotime($item->close) < time()) {
          $is_closed = TRUE;
        }

        return ($is_opened && !$is_closed) ? TRUE : FALSE;
    }

    return FALSE;
  }

  /**
   * Determines if the webform is currently closed but scheduled to open.
   *
   * @param \Drupal\webform\WebformInterface|null $webform
   *   The webform entity reference webform.
   * @param \Drupal\webform\Plugin\Field\FieldType\WebformEntityReferenceItem $item
   *   The webform entity reference item.
   *
   * @return bool
   *   TRUE if the webform is currently closed but scheduled to open.
   *
   * @see \Drupal\webform\WebformInterface::isOpening
   * @see \Drupal\webform\entity\Webform::isOpening
   */
  protected function isOpening(WebformInterface $webform, WebformEntityReferenceItem $item) {
    // Make sure the webform exists.
    if (!$webform) {
      return FALSE;
    }

    if (!$webform->isOpen() && $webform->isOpening()) {
      return TRUE;
    }

    $is_scheduled = ($item->status === WebformInterface::STATUS_SCHEDULED);
    $is_opening = ($item->open && strtotime($item->open) > time());
    return ($is_scheduled  && $is_opening) ? TRUE : FALSE;
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
    $config = \Drupal::config('webform.settings');
    \Drupal::service('renderer')->addCacheableDependency($elements, $config);

    // Track if the webfor is updated.
    \Drupal::service('renderer')->addCacheableDependency($elements, $webform);

    // Calculate the max-age based on the open/close data/time for the item
    // and webform.
    $max_age = 0;
    $states = ['open', 'close'];
    foreach ($states as $state) {
      if ($item->status === WebformInterface::STATUS_SCHEDULED) {
        $item_state = $item->$state;
        if ($item_state && strtotime($item_state) > time()) {
          $item_seconds = strtotime($item_state) - time();
          if (!$max_age || $item_seconds > $max_age) {
            $max_age = $item_seconds;
          }
        }
      }
      if ($webform->status() === WebformInterface::STATUS_SCHEDULED) {
        $webform_state = $webform->get($state);
        if ($webform_state && strtotime($webform_state) > time()) {
          $webform_seconds = strtotime($webform_state) - time();
          if (!$max_age || $webform_seconds > $max_age) {
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
