<?php

namespace Drupal\webform_test_element\Element;

use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a webform composite element for testing.
 *
 * @FormElement("webform_test_composite")
 */
class WebformTestComposite extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element) {
    $elements = [];
    $elements['textfield'] = [
      '#type' => 'textfield',
      '#title' => t('textfield'),
    ];
    $elements['email'] = [
      '#type' => 'email',
      '#title' => t('email'),
    ];
    $elements['webform_email_confirm'] = [
      '#type' => 'webform_email_confirm',
      '#title' => t('webform_email_confirm'),
    ];
    $elements['tel'] = [
      '#type' => 'tel',
      '#title' => t('tel'),
      '#international' => TRUE,
    ];
    $elements['select'] = [
      '#type' => 'select',
      '#title' => t('select'),
      '#options' => [
        'one' => t('One'),
        'two' => t('Two'),
        'three' => t('Three'),
      ],
      '#select2' => TRUE,
    ];
    $elements['radios'] = [
      '#type' => 'radios',
      '#title' => t('radios'),
      '#options' => [
        'one' => t('One'),
        'two' => t('Two'),
        'three' => t('Three'),
      ],
    ];
    $elements['date'] = [
      '#type' => 'date',
      '#title' => t('date'),
    ];
    $elements['webform_entity_select'] = [
      '#type' => 'webform_entity_select',
      '#title' => t('webform_entity_select'),
      '#target_type' => 'user',
      '#selection_handler' => 'default:user',
      '#selection_settings' => [
        'include_anonymous' => TRUE,
      ],
    ];
    $elements['entity_autocomplete'] = [
      '#type' => 'entity_autocomplete',
      '#title' => t('entity_autocomplete'),
      '#target_type' => 'user',
      '#selection_handler' => 'default:user',
      '#selection_settings' => [
        'include_anonymous' => TRUE,
      ],
    ];
    $elements['datelist'] = [
      '#type' => 'datelist',
      '#title' => t('datelist'),
    ];
    $elements['datetime'] = [
      '#type' => 'datetime',
      '#title' => t('datetime'),
    ];
    $elements['fieldset'] = [
      '#type' => 'fieldset',
      '#title' => t('fieldset'),
    ];
    $elements['fieldset']['nested_tel'] = [
      '#type' => 'tel',
      '#title' => t('nested_tel'),
      '#international' => TRUE,
    ];
    $elements['fieldset']['nested_select'] = [
      '#type' => 'select',
      '#title' => t('nested_select'),
      '#options' => 'days',
    ];
    $elements['fieldset']['nested_radios'] = [
      '#type' => 'radios',
      '#title' => t('nested_radios'),
      '#options' => 'days',
    ];

    // Below elements throw exceptions.
    // @see \Drupal\webform\Element\WebformCompositeBase::processWebformComposite
    // $elements['checkboxes'] = ['#type' => 'checkboxes'];
    // $elements['likert'] = ['#type' => 'webform_likert'];
    // $elements['datetime'] = ['#type' => 'datetime'];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return [];
  }

}
