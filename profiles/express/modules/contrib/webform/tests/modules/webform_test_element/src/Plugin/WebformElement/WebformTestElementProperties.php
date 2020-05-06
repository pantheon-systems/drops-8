<?php

namespace Drupal\webform_test_element\Plugin\WebformElement;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformAjaxElementTrait;
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

  use WebformAjaxElementTrait;

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return parent::defineDefaultProperties() + [
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

    $form['test'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Test'),
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

    $this->buildAjaxElement(
      'test-element',
      $form['test'],
      $form['test']['property_a']
    );
    // Must explicitly set properties to be included in $form_state.
    $form['test']['update']['#limit_validation_errors'] = [
      ['properties', 'property_a'],
      ['properties', 'property_b'],
      ['properties', 'property_c'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function submitAjaxElementCallback(array $form, FormStateInterface $form_state) {
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

}
