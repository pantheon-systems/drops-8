<?php

namespace Drupal\webform\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;

/**
 * Plugin implementation of the 'webform_entity_reference_select' widget.
 *
 * @FieldWidget(
 *   id = "webform_entity_reference_select",
 *   label = @Translation("Select list"),
 *   description = @Translation("A select menu field."),
 *   field_types = {
 *     "webform"
 *   }
 * )
 *
 * @see \Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase
 */
class WebformEntityReferenceSelectWidget extends OptionsWidgetBase {

  use WebformEntityReferenceWidgetTrait;

  /**
   * {@inheritdoc}
   */
  public function getTargetIdElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Get default value (webform ID).
    $referenced_entities = $items->referencedEntities();
    $default_value = isset($referenced_entities[$delta]) ? $referenced_entities[$delta] : NULL;
    // Convert default_value's Webform to a simple entity_id.
    if ($default_value instanceof WebformInterface) {
      $default_value = $default_value->id();
    }

    // Get options grouped by category.
    $options = $this->getOptions($items->getEntity());
    // Make sure if an archived webform is the #default_value always include
    // it as an option.
    if ($default_value && $webform = Webform::load($default_value)) {
      if ($webform->isArchived()) {
        $options[(string) t('Archived')][$webform->id()] = $webform->label();
      }
    }

    $target_element = [
      '#type' => 'webform_entity_select',
      '#options' => $options,
      '#default_value' => $default_value,
    ];

    // Set empty option.
    if (empty($element['#required'])) {
      $target_element['#empty_option'] = $this->t('- Select -');
      $target_element['#empty_value'] = '';
    }

    // Set validation callback.
    $target_element['#element_validate'] = [[get_class($this), 'validateWebformEntityReferenceSelectWidget']];

    return $target_element;
  }

  /**
   * Webform element validation handler for entity_select elements.
   */
  public static function validateWebformEntityReferenceSelectWidget(&$element, FormStateInterface $form_state, &$complete_form) {
    // Below prevents the below error.
    // Fatal error: Call to a member function uuid() on a non-object in
    // core/lib/Drupal/Core/Field/EntityReferenceFieldItemList.php.
    $value = (!empty($element['#value'])) ? $element['#value'] : NULL;
    $form_state->setValueForElement($element, $value);
  }

  /**
   * Returns the array of options for the widget.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity for which to return options.
   *
   * @return array
   *   The array of options for the widget.
   */
  protected function getOptions(FieldableEntityInterface $entity) {
    if (!isset($this->options)) {
      // Limit the settable options for the current user account.
      // Note: All active webforms are returned and grouped by category.
      // @see \Drupal\webform\Plugin\Field\FieldType\WebformEntityReferenceItem::getSettableOptions
      // @see \Drupal\webform\WebformEntityStorageInterface::getOptions
      $options = $this->fieldDefinition
        ->getFieldStorageDefinition()
        ->getOptionsProvider($this->column, $entity)
        ->getSettableOptions(\Drupal::currentUser());

      $module_handler = \Drupal::moduleHandler();
      $context = [
        'fieldDefinition' => $this->fieldDefinition,
        'entity' => $entity,
      ];
      $module_handler->alter('options_list', $options, $context);

      array_walk_recursive($options, [$this, 'sanitizeLabel']);
      $this->options = $options;
    }
    return $this->options;
  }

}
