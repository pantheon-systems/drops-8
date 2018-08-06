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
  public function getDefaultProperties() {
    return [
      'default_value' => FALSE,
    ] + parent::getDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    $format = $this->getItemFormat($element);

    switch ($format) {
      case 'value':
        return ($value) ? $this->t('Yes') : $this->t('No');

      default:
        return ($value) ? 1 : 0;
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
