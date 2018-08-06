<?php

namespace Drupal\webform_test_element\Plugin\WebformElement;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElementBase;

/**
 * Provides a 'webform_test_element_properties' element.
 *
 * @WebformElement(
 *   id = "webform_test_element_properties",
 *   label = @Translation("Test element properties (Ajax)"),
 *   description = @Translation("Provides a form element for testing Ajax support.")
 * )
 */
class WebformTestElementProperties extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + [
      'property_a' => 'Three',
      'property_b' => 'Three',
      'property_c' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Get element properties.
    $element_properties = $form_state->get('element_properties');
    $element_properties['property_c'] = date('c');
    $form_state->set('element_properties', $element_properties);

    // Generate a unique wrapper HTML ID.
    $ajax_wrapper_id = Html::getUniqueId('ajax-wrapper');

    $form['test'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Test'),
      '#prefix' => '<div id="' . $ajax_wrapper_id . '">',
      '#suffix' => '</div>',
    ];

    $form['test']['property_a'] = [
      '#type' => 'select',
      '#title' => $this->t('Property A'),
      '#description' => $this->t('Changing this value will update B and C'),
      '#options' => [
        'One' => $this->t('One'),
        'Two' => $this->t('Two'),
        'Three' => $this->t('Three'),
      ],
      // @see Drupal.behaviors.webformSubmitTrigger
      '#attributes' => ['data-webform-trigger-submit' => '.js-webform-test-properties-submit'],
    ];

    $form['test']['property_b'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Property B'),
      '#description' => $this->t('Property C is always equal to Property A.'),
      '#attributes' => ['readonly' => TRUE, 'style' => 'background-color: #eee'],
    ];

    $form['test']['property_c'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Property C'),
      '#description' => $this->t('Property C is always updated to reflect the current timestamp.'),
      '#attributes' => ['readonly' => TRUE, 'style' => 'background-color: #eee'],
    ];

    $form['test']['update'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
      // Set access to make sure the button is visible.
      '#access' => TRUE,
      // Disable validation.
      '#validate' => [],
      // Must explicitly set properties to be included in $form_state.
      '#limit_validation_errors' => [
        ['properties', 'property_a'],
        ['properties', 'property_b'],
        ['properties', 'property_c'],
      ],
      // Submit the form and update test property_c.
      '#submit' => [[get_called_class(), 'testPropertiesSubmitCallback']],
      // Refresh the test details container.
      // Comment out the below code to disable Ajax callback.
      '#ajax' => [
        'callback' => [get_called_class(), 'testPropertiesAjaxCallback'],
        'wrapper' => $ajax_wrapper_id,
        'progress' => ['type' => 'fullscreen'],
      ],
      // Hide button, add submit button trigger class, and disable validation.
      '#attributes' => [
        'class' => [
          'js-hide',
          'js-webform-test-properties-submit',
          'js-webform-novalidate',
        ],
      ],
    ];

    // Attached webform.form library for .js-webform-novalidate behavior.
    $form['#attached']['library'][] = 'webform/webform.form';

    return $form;
  }

  /**
   * Properties submit callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function testPropertiesSubmitCallback(array $form, FormStateInterface $form_state) {
    // Get the webform test container by going up one level.
    $button = $form_state->getTriggeringElement();
    $element =& NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));

    // Update timestamp on 'property_b'.
    NestedArray::setValue($form_state->getUserInput(), $element['property_b']['#parents'], $element['property_a']['#value']);
    $form_state->setValueForElement($element['property_b'], $element['property_a']['#value']);

    // Update timestamp on 'property_c'.
    $property_c = date('c');
    NestedArray::setValue($form_state->getUserInput(), $element['property_c']['#parents'], $property_c);
    $form_state->setValueForElement($element['property_c'], $property_c);

    // Rebuild the form.
    $form_state->setRebuild();
  }

  /**
   * Properties Ajax callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The properties element.
   */
  public static function testPropertiesAjaxCallback(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    return $element;
  }

}
