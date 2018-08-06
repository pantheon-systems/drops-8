<?php

namespace Drupal\webform_views\WebformElementViews;

use Drupal\webform\Plugin\WebformElementInterface;

/**
 * Webform views handler for numeric webform elements.
 */
class WebformNumericViews extends WebformDefaultViews {

  /**
   * {@inheritdoc}
   */
  public function getElementViewsData(WebformElementInterface $element_plugin, array $element) {
    $views_data = parent::getElementViewsData($element_plugin, $element);

    $views_data['field']['id'] = 'webform_submission_field_numeric';
    $views_data['sort']['id'] = 'webform_submission_field_numeric_sort';

    $views_data['filter'] = [
      'id' => 'webform_submission_numeric_filter',
      'real field' => 'value',
      'explicit_cast' => TRUE,
    ];

    return $views_data;
  }

}
