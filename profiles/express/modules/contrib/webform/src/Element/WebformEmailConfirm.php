<?php

namespace Drupal\webform\Element;

use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Utility\WebformElementHelper;

/**
 * Provides a webform element requiring users to double-element and confirm an email address.
 *
 * Formats as a pair of email addresses fields, which do not validate unless
 * the two entered email addresses match.
 *
 * @FormElement("webform_email_confirm")
 */
class WebformEmailConfirm extends FormElement {

  use WebformCompositeFormElementTrait;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#size' => 60,
      '#process' => [
        [$class, 'processWebformEmailConfirm'],
      ],
      '#pre_render' => [
        [$class, 'preRenderWebformCompositeFormElement'],
      ],
      '#required' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      if (!isset($element['#default_value'])) {
        $element['#default_value'] = '';
      }
      return [
        'mail_1' => $element['#default_value'],
        'mail_2' => $element['#default_value'],
      ];
    }
    else {
      return $input;
    }
  }

  /**
   * Expand an email confirm field into two HTML5 email elements.
   */
  public static function processWebformEmailConfirm(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#tree'] = TRUE;

    // Get shared properties.
    $shared_properties = [
      '#title_display',
      '#description_display',
      '#size',
      '#maxlength',
      '#pattern',
      '#pattern_error',
      '#required',
      '#required_error',
      '#placeholder',
      '#attributes',
    ];
    $element_shared_properties = [
      '#type' => 'email',
      '#webform_element' => TRUE,
    ] + array_intersect_key($element, array_combine($shared_properties, $shared_properties));
    // Copy wrapper attributes to shared element attributes.
    if (isset($element['#wrapper_attributes'])
      && isset($element['#wrapper_attributes']['class'])) {
      foreach ($element['#wrapper_attributes']['class'] as $index => $class) {
        if (in_array($class, ['js-webform-tooltip-element', 'webform-tooltip-element'])) {
          $element_shared_properties['#wrapper_attributes']['class'][] = $class;
          unset($element['#wrapper_attributes']['class'][$index]);
        }
      }
    }

    // Get mail 1 email element.
    $mail_1_properties = [
      '#title',
      '#description',
    ];
    $element['mail_1'] = $element_shared_properties + array_intersect_key($element, array_combine($mail_1_properties, $mail_1_properties));
    $element['mail_1']['#attributes']['class'][] = 'webform-email';
    $element['mail_1']['#value'] = empty($element['#value']) ? NULL : $element['#value']['mail_1'];
    $element['mail_1']['#error_no_message'] = TRUE;

    // Build mail_2 confirm email element.
    $element['mail_2'] = $element_shared_properties;
    $element['mail_2']['#title'] = t('Confirm email');
    foreach ($element as $key => $value) {
      if (strpos($key, '#confirm__') === 0) {
        $element['mail_2'][str_replace('#confirm__', '#', $key)] = $value;
      }
    }
    $element['mail_2']['#attributes']['class'][] = 'webform-email-confirm';
    $element['mail_2']['#value'] = empty($element['#value']) ? NULL : $element['#value']['mail_2'];
    $element['mail_2']['#error_no_message'] = TRUE;

    // Initialize the mail elements to allow for webform enhancements.
    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
    $element_manager = \Drupal::service('plugin.manager.webform.element');
    $element_manager->buildElement($element['mail_1'], $complete_form, $form_state);
    $element_manager->buildElement($element['mail_2'], $complete_form, $form_state);

    // Don't require the main element.
    $element['#required'] = FALSE;

    // Hide title and description from being display.
    $element['#title_display'] = 'invisible';
    $element['#description_display'] = 'invisible';

    // Remove properties that are being applied to the sub elements.
    unset($element['#maxlength']);
    unset($element['#attributes']);
    unset($element['#description']);

    // Add validate callback.
    $element += ['#element_validate' => []];
    array_unshift($element['#element_validate'], [get_called_class(), 'validateWebformEmailConfirm']);

    // Add flexbox support.
    if (!empty($element['#flexbox'])) {
      $flex_wrapper = [
        '#prefix' => '<div class="webform-flex webform-flex--1"><div class="webform-flex--container">',
        '#suffix' => '</div></div>',
      ];
      $element['flexbox'] = [
        '#type' => 'webform_flexbox',
        'mail_1' => $element['mail_1'] + $flex_wrapper + [
          '#parents' => array_merge($element['#parents'], ['mail_1']),
        ],
        'mail_2' => $element['mail_2'] + $flex_wrapper + [
          '#parents' => array_merge($element['#parents'], ['mail_2']),
        ],
      ];
      unset($element['mail_1'], $element['mail_2']);
    }

    return $element;
  }

  /**
   * Validates an email confirm element.
   */
  public static function validateWebformEmailConfirm(&$element, FormStateInterface $form_state, &$complete_form) {
    if (isset($element['flexbox'])) {
      $mail_element =& $element['flexbox'];
    }
    else {
      $mail_element =& $element;
    }

    $mail_1 = trim($mail_element['mail_1']['#value']);
    $mail_2 = trim($mail_element['mail_2']['#value']);
    $has_access = (!isset($element['#access']) || $element['#access'] === TRUE);
    if ($has_access) {
      // Compare email addresses.
      if ((!empty($mail_1) || !empty($mail_2)) && strcmp($mail_1, $mail_2)) {
        $form_state->setError($element, t('The specified email addresses do not match.'));
      }
      else {
        // NOTE: Only mail_1 needs to be validated since mail_2 is the same value.
        // Verify the required value.
        if ($mail_element['mail_1']['#required'] && empty($mail_1)) {
          $required_error_title = (isset($mail_element['mail_1']['#title'])) ? $mail_element['mail_1']['#title'] : NULL;
          WebformElementHelper::setRequiredError($element, $form_state, $required_error_title);
        }
        // Verify that the value is not longer than #maxlength.
        if (isset($mail_element['mail_1']['#maxlength']) && mb_strlen($mail_1) > $mail_element['mail_1']['#maxlength']) {
          $t_args = [
            '@name' => $mail_element['mail_1']['#title'],
            '%max' => $mail_element['mail_1']['#maxlength'],
            '%length' => mb_strlen($mail_1),
          ];
          $form_state->setError($element, t('@name cannot be longer than %max characters but is currently %length characters long.', $t_args));
        }
      }

      // Add email validation errors for inline form errors.
      // @see \Drupal\Core\Render\Element\Email::validateEmail
      $inline_errors = empty($complete_form['#disable_inline_form_errors'])
        && \Drupal::moduleHandler()->moduleExists('inline_form_errors');
      $mail_error = $form_state->getError($mail_element['mail_1']);
      if ($inline_errors && $mail_error) {
        $form_state->setError($element, $mail_error);
      }
    }

    // Set #title for other validation callbacks.
    // @see \Drupal\webform\Plugin\WebformElementBase::validateUnique
    if (isset($mail_element['mail_1']['#title'])) {
      $element['#title'] = $mail_element['mail_1']['#title'];
    }

    // Email field must be converted from a two-element array into a single
    // string regardless of validation results.
    $form_state->setValueForElement($mail_element['mail_1'], NULL);
    $form_state->setValueForElement($mail_element['mail_2'], NULL);

    $element['#value'] = $mail_1;
    $form_state->setValueForElement($element, $mail_1);
  }

}
