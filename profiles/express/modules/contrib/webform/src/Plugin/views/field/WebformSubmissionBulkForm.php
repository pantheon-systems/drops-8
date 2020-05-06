<?php

namespace Drupal\webform\Plugin\views\field;

use Drupal\views\Plugin\views\field\BulkForm;

/**
 * Defines a webform submission operations bulk form element.
 *
 * @ViewsField("webform_submission_bulk_form")
 */
class WebformSubmissionBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return $this->t('No submission selected.');
  }

}
