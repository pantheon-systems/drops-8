<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a base 'boolean' class.
 */
abstract class BooleanBase extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      'default_value' => FALSE,
      'return_value' => '',
    ] + parent::defineDefaultProperties();
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  protected function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    $format = $this->getItemFormat($element);

    switch ($format) {
      case 'value':
        return ($value) ? $this->t('Yes') : $this->t('No');

      default:
        // If a #return_value is defined then return it.
        if (!empty($element['#return_value'])) {
          return ($value) ? $value : 0;
        }
        else {
          return ($value) ? 1 : 0;
        }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Add return value to default value details.
    $form['default']['#title'] = $this->t('Return/default value');
    $form['default']['return_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Return value'),
      '#description' => $this->t('The return value is what is submitted to the server and stored in the database when the element is checked. The default value and recommended return value is a TRUE boolean value.')
        . $this->t('<br/><br/>')
        . $this->t('<strong>The return value should only be customized when an external system or service expects a custom string value. (i.e. yes, checked, accepted, etc…)</strong>'),
      '#weight' => -20,
    ];
    // Change default value to select boolean.
    $form['default']['default_value'] = [
      '#title' => $this->t('Default value'),
      '#type' => 'select',
      '#options' => [
        0 => $this->t('Unchecked'),
        1 => $this->t('Checked'),
      ],
    ];

    return $form;
  }

}
