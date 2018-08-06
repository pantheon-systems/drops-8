<?php

namespace Drupal\webform;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the webform entity type.
 */
class WebformEntityViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['webform']['webform_bulk_form'] = [
      'title' => $this->t('Webform operations bulk form'),
      'help' => $this->t('Add a form element that lets you run operations on multiple webform.'),
      'field' => [
        'id' => 'webform_bulk_form',
      ],
    ];

    return $data;
  }

}
