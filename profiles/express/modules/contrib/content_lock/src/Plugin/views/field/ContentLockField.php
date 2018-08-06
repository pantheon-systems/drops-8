<?php

namespace Drupal\content_lock\Plugin\views\field;

use Drupal\views\Plugin\views\field\Boolean;
use Drupal\views\ResultRow;

/**
 * A handler to provide proper displays for dates.
 *
 * @group views_field_handlers
 *
 * @ViewsField("content_lock_field")
 */
class ContentLockField extends Boolean {

  /**
   * Query.
   */
  public function query() {
    $this->ensureMyTable();
    $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $values->content_lock_timestamp ? TRUE : FALSE;
    if (!empty($this->options['not'])) {
      $value = !$value;
    }

    switch ($this->options['type']) {
      case 'true-false':
        return $value ? t('True') : t('False');

      case 'on-off':
        return $value ? t('On') : t('Off');

      case 'yes-no':
      default:
        return $value ? t('Yes') : t('No');
    }
  }

}
