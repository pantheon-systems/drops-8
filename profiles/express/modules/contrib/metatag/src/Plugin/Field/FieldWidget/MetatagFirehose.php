<?php

namespace Drupal\metatag\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\metatag\MetatagManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Advanced widget for metatag field.
 *
 * @FieldWidget(
 *   id = "metatag_firehose",
 *   label = @Translation("Advanced meta tags form"),
 *   field_types = {
 *     "metatag"
 *   }
 * )
 */
class MetatagFirehose extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Instance of MetatagManager service.
   */
  protected $metatagManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('metatag.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, MetatagManager $manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->metatagManager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $item = $items[$delta];
    $default_tags = metatag_get_default_tags();

    // Retrieve the values for each metatag from the serialized array.
    $values = [];
    if (!empty($item->value)) {
      $values = unserialize($item->value);
    }

    // Populate fields which have not been overridden in the entity.
    if (!empty($default_tags)) {
      foreach ($default_tags as $tag_id => $tag_value) {
        if (!isset($values[$tag_id]) && !empty($tag_value)) {
          $values[$tag_id] = $tag_value;
        }
      }
    }

    // Create the form element.
    $element = $this->metatagManager->form($values, $element, [$item->getEntity()->getentityTypeId()]);

    // Put the form element into the form's "advanced" group.
    $element['#group'] = 'advanced';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // Flatten the values array to remove the groups and then serialize all the
    // metatags into one value for storage.
    $tag_manager = \Drupal::service('plugin.manager.metatag.tag');
    $tags = $tag_manager->getDefinitions();
    foreach ($values as &$value) {
      $flattened_value = [];
      foreach ($value as $group) {
        // Exclude the "original delta" value.
        if (is_array($group)) {
          foreach ($group as $tag_id => $tag_value) {
            $tag = $tag_manager->createInstance($tag_id);
            $tag->setValue($tag_value);
            if (!empty($tag->value())) {
              $flattened_value[$tag_id] = $tag->value();
            }
          }
        }
      }
      $value = serialize($flattened_value);
    }

    return $values;
  }

}
