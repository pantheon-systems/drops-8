<?php

namespace Drupal\webform\Element;

/**
 * Provides a webform element for a select menu with an other option.
 *
 * See #empty_option and #empty_value for an explanation of various settings for
 * a select element, including behavior if #required is TRUE or FALSE.
 *
 * @FormElement("webform_select_other")
 */
class WebformSelectOther extends WebformOtherBase {

  /**
   * {@inheritdoc}
   */
  protected static $type = 'select';

  /**
   * {@inheritdoc}
   */
  protected static $properties = [
    '#required',
    '#options',
    '#default_value',
    '#attributes',

    '#multiple',
    '#empty_value',
    '#empty_option',

    '#ajax',
  ];

}
