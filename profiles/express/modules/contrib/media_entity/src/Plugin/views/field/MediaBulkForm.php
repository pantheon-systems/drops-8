<?php

namespace Drupal\media_entity\Plugin\views\field;

use Drupal\system\Plugin\views\field\BulkForm;

/**
 * Defines a media operations bulk form element.
 *
 * @ViewsField("media_bulk_form")
 */
class MediaBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return $this->t('No media selected.');
  }

}
