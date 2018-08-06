<?php

namespace Drupal\webform_example_composite\Element;

use Drupal\Component\Utility\Html;
use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a 'webform_example_composite'.
 *
 * Webform composites contain a group of sub-elements.
 *
 *
 * IMPORTANT:
 * Webform composite can not contain multiple value elements (i.e. checkboxes)
 * or composites (i.e. webform_address)
 *
 * @FormElement("webform_example_composite")
 *
 * @see \Drupal\webform\Element\WebformCompositeBase
 * @see \Drupal\webform_example_composite\Element\WebformExampleComposite
 */
class WebformExampleComposite extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + ['#theme' => 'webform_example_composite'];
  }

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements() {
    // Generate an unique ID that can be used by #states.
    $html_id = Html::getUniqueId('webform_example_composite');

    $elements = [];
    $elements['first_name'] = [
      '#type' => 'textfield',
      '#title' => t('First name'),
      '#attributes' => ['data-webform-composite-id' => $html_id . '--first_name'],
    ];
    $elements['last_name'] = [
      '#type' => 'textfield',
      '#title' => t('Last name'),
      '#attributes' => ['data-webform-composite-id' => $html_id . '--last_name'],
    ];
    $elements['date_of_birth'] = [
      '#type' => 'date',
      '#title' => t('Date of birth'),
      '#states' => [
        'enabled' => [
          '[data-webform-composite-id="' . $html_id . '--first_name"]' => ['filled' => TRUE],
          '[data-webform-composite-id="' . $html_id . '--last_name"]' => ['filled' => TRUE],
        ],
      ],
    ];
    $elements['gender'] = [
      '#type' => 'select',
      '#title' => t('Gender'),
      '#options' => 'gender',
      '#empty_option' => '',
      '#states' => [
        'enabled' => [
          '[data-webform-composite-id="' . $html_id . '--first_name"]' => ['filled' => TRUE],
          '[data-webform-composite-id="' . $html_id . '--last_name"]' => ['filled' => TRUE],
        ],
      ],
    ];
    return $elements;
  }

}
