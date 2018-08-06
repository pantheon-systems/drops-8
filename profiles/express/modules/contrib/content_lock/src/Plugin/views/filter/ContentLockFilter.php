<?php

namespace Drupal\content_lock\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\BooleanOperator;

/**
 * Filter handler for content lock.
 *
 * @group views_filter_handlers
 *
 * @ViewsFilter("content_lock_filter")
 */
class ContentLockFilter extends BooleanOperator {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    if (empty($this->value)) {
      $this->query->addWhere($this->options['group'], $this->tableAlias . ".timestamp", "NULL", "=");
    }
    else {
      $this->query->addWhere($this->options['group'], $this->tableAlias . ".timestamp", "NULL", "<>");
    }
  }

}
