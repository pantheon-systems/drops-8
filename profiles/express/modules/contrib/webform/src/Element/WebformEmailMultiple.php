<?php

namespace Drupal\webform\Element;

use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a webform element for entering multiple comma delimited email addresses.
 *
 * @FormElement("webform_email_multiple")
 */
class WebformEmailMultiple extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#description' => $this->t('Multiple email addresses may be separated by commas.'),
      '#size' => 60,
      '#cardinality' => NULL,
      '#allow_tokens' => FALSE,
      '#process' => [
        [$class, 'processWebformEmailConfirm'],
        [$class, 'processAutocomplete'],
        [$class, 'processAjaxForm'],
        [$class, 'processPattern'],
      ],
      '#pre_render' => [
        [$class, 'preRenderWebformEmailMultiple'],
      ],
      '#theme' => 'input__webform_email_multiple',
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * Process email multiple element.
   */
  public static function processWebformEmailConfirm(&$element, FormStateInterface $form_state, &$complete_form) {
    // Add validate callback.
    $element += ['#element_validate' => []];
    array_unshift($element['#element_validate'], [get_called_class(), 'validateWebformEmailMultiple']);
    return $element;
  }

  /**
   * Webform element validation handler for #type 'webform_email_multiple'.
   */
  public static function validateWebformEmailMultiple(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = trim($element['#value']);

    $element['#value'] = $value;
    $form_state->setValueForElement($element, $value);

    if ($value) {
      $values = preg_split('/\s*,\s*/', $value);
      // Validate email.
      foreach ($values as $value) {
        // Allow tokens to be included in multiple email list by skipping
        // validation if a token is present.
        if (!empty($element['#allow_tokens'] && preg_match('/\[.+\]/', $value))) {
          continue;
        }

        if (!\Drupal::service('email.validator')->isValid($value)) {
          $form_state->setError($element, t('The email address %mail is not valid.', ['%mail' => $value]));
          return;
        }
      }

      // Validate cardinality.
      if ($element['#cardinality'] && count($values) > $element['#cardinality']) {
        if (isset($element['#cardinality_error'])) {
          $form_state->setError($element, $element['#cardinality_error']);
        }
        elseif (isset($element['#title'])) {
          $t_args = [
            '%name' => empty($element['#title']) ? $element['#parents'][0] : $element['#title'],
            '@count' => $element['#cardinality'],
          ];
          $error_message = \Drupal::translation()->formatPlural(
            $element['#cardinality'],
            '%name: this element cannot hold more than @count value.',
            '%name: this element cannot hold more than @count values.',
            $t_args
          );
          $form_state->setError($element, $error_message);
        }
        else {
          $form_state->setError($element);
        }
      }
    }
  }

  /**
   * Prepares a #type 'webform_email_multiple' render element for theme_element().
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #size, #maxlength,
   *   #placeholder, #required, #attributes.
   *
   * @return array
   *   The $element with prepared variables ready for theme_element().
   */
  public static function preRenderWebformEmailMultiple(array $element) {
    $element['#attributes']['type'] = 'text';
    Element::setAttributes($element, ['id', 'name', 'value', 'size', 'maxlength', 'placeholder']);
    static::setAttributes($element, ['form-text', 'webform-email-multiple']);
    return $element;
  }

}
