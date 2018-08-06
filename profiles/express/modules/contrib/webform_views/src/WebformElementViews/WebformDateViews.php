<?php

namespace Drupal\webform_views\WebformElementViews;

use Drupal\webform\Plugin\WebformElementInterface;

/**
 * Webform views handler for date-based webform elements.
 */
class WebformDateViews extends WebformDefaultViews {

  /**
   * {@inheritdoc}
   */
  public function getElementViewsData(WebformElementInterface $element_plugin, array $element) {
    $views_data = parent::getElementViewsData($element_plugin, $element);

    $views_data['filter'] = [
      'id' => 'webform_submission_numeric_filter',
      'real field' => 'value',
      'explicit_cast' => FALSE,
    ];

    return $views_data;
  }

}
