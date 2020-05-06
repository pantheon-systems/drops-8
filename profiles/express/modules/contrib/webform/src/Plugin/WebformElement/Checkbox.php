<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'checkbox' element.
 *
 * @WebformElement(
 *   id = "checkbox",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Checkbox.php/class/Checkbox",
 *   label = @Translation("Checkbox"),
 *   description = @Translation("Provides a form element for a single checkbox."),
 *   category = @Translation("Basic elements"),
 * )
 */
class Checkbox extends BooleanBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $properties = [
      'title_display' => 'after',
      // Checkbox.
      'exclude_empty' => FALSE,
    ] + parent::defineDefaultProperties();
    unset(
      $properties['unique'],
      $properties['unique_entity'],
      $properties['unique_user'],
      $properties['unique_error'],
      $properties['format_items'],
      $properties['format_items_html'],
      $properties['format_items_text']
    );
    return $properties;
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  protected function build($format, array &$element, WebformSubmissionInterface $webform_submission, array $options = []) {
    if ($this->isEmptyExcluded($element, $options) && !$this->getValue($element, $webform_submission, $options)) {
      return NULL;
    }
    else {
      return parent::build($format, $element, $webform_submission, $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isEmptyExcluded(array $element, array $options) {
    $options += [
      'exclude_empty_checkbox' => FALSE,
    ];

    return $this->getElementProperty($element, 'exclude_empty') ?: $options['exclude_empty_checkbox'];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['display']['exclude_empty'] = [
      '#title' => $this->t('Exclude unselected checkbox'),
      '#type' => 'checkbox',
      '#return_value' => TRUE,
    ];

    return $form;
  }

}
