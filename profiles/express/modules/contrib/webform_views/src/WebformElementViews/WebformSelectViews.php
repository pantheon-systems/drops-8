<?php

namespace Drupal\webform_views\WebformElementViews;

use Drupal\webform\Plugin\WebformElementInterface;

/**
 * Webform views handler for select webform elements.
 */
class WebformSelectViews extends WebformElementViewsAbstract {

  /**
   * {@inheritdoc}
   */
  public function getElementViewsData(WebformElementInterface $element_plugin, array $element) {
    $views_data = parent::getElementViewsData($element_plugin, $element);

    $views_data['filter'] = [
      'id' => 'webform_submission_select_filter',
      'real field' => 'value',
    ];

    return $views_data;
  }

}
