<?php

namespace Drupal\webform_views\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\SortPluginBase;
use Drupal\webform_views\Plugin\views\WebformSubmissionCastToNumberTrait;

/**
 * Sorting by numeric webform submission field.
 *
 * @ViewsSort("webform_submission_field_numeric_sort")
 */
class WebformSubmissionFieldNumericSort extends SortPluginBase {

  use WebformSubmissionCastToNumberTrait;

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    $field = $this->query->addField(NULL, $this->castToDataType($this->tableAlias . '.' . $this->realField), $this->realField . '_sort');
    $this->query->addOrderBy(NULL, NULL, $this->options['order'], $field);
  }

}
