<?php

namespace Drupal\webform_views\Plugin\views\field;

use Drupal\webform_views\Plugin\views\WebformSubmissionCastToNumberTrait;

/**
 * Webform submission numeric field.
 *
 * @ViewsField("webform_submission_field_numeric")
 */
class WebformSubmissionFieldNumeric extends WebformSubmissionField {

  use WebformSubmissionCastToNumberTrait;

  /**
   * {@inheritdoc}
   */
  public function clickSort($order) {
    $this->ensureMyTable();

    $field = $this->query->addField(NULL, $this->castToDataType($this->tableAlias . '.value'), $this->realField . '_sort');

    $params = $this->options['group_type'] != 'group' ? ['function' => $this->options['group_type']] : [];
    $this->query->addOrderBy(NULL, NULL, $order, $field, $params);
  }

}
