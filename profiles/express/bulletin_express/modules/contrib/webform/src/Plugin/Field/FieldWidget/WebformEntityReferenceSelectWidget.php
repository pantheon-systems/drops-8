<?php

namespace Drupal\webform\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformInterface;

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
 * @see \Drupal\webform\Plugin\Field\FieldWidget\WebformEntityReferenceAutocompleteWidget
 * @see \Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget
 * @see \Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget
 */
class WebformEntityReferenceSelectWidget extends WebformEntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Convert 'entity_autocomplete' to 'webform_entity_select' element.
    $element['target_id']['#type'] = 'webform_entity_select';

    /** @var \Drupal\webform\WebformEntityStorageInterface $webform_storage */
    $webform_storage = \Drupal::service('entity_type.manager')->getStorage('webform');
    $element['target_id']['#options'] = $webform_storage->getOptions(FALSE);

    // Set empty option.
    if (empty($element['#required'])) {
      $element['target_id']['#empty_option'] = $this->t('- Select -');
      $element['target_id']['#empty_value'] = '';
    }

    // Convert default_value's Webform to a simple entity_id.
    if (!empty($element['target_id']['#default_value']) && $element['target_id']['#default_value'] instanceof WebformInterface) {
      $element['target_id']['#default_value'] = $element['target_id']['#default_value']->id();
    }

    // Remove properties that are not applicable.
    unset($element['target_id']['#size']);
    unset($element['target_id']['#maxlength']);
    unset($element['target_id']['#placeholder']);

    $element['#element_validate'] = [[get_class($this), 'validateWebformEntityReferenceSelectWidget']];

    return $element;
  }

  /**
   * Webform element validation handler for entity_select elements.
   */
  public static function validateWebformEntityReferenceSelectWidget(&$element, FormStateInterface $form_state, &$complete_form) {
    // Below prevents the below error.
    // Fatal error: Call to a member function uuid() on a non-object in
    // core/lib/Drupal/Core/Field/EntityReferenceFieldItemList.php.
    $value = (!empty($element['target_id']['#value'])) ? $element['target_id']['#value'] : NULL;
    $form_state->setValueForElement($element['target_id'], $value);
  }

}
