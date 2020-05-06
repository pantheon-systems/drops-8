<?php

namespace Drupal\webform_access;

use Drupal\webform\Form\WebformConfigEntityDeleteFormBase;

/**
 * Provides a delete webform access type form.
 */
class WebformAccessTypeDeleteForm extends WebformConfigEntityDeleteFormBase {

  /**
   * {@inheritdoc}
   */
  protected $confirmCheckbox = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return [
      'title' => [
        '#markup' => $this->t('This action will…'),
      ],
      'list' => [
        '#theme' => 'item_list',
        '#items' => [
          $this->t('Remove configuration'),
          $this->t('Affect any access groups which use this type'),
        ],
      ],
    ];
  }

}
