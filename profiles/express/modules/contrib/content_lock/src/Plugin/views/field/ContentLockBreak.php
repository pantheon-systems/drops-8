<?php

namespace Drupal\content_lock\Plugin\views\field;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to an entity.
 *
 * @group views_field_handlers
 *
 * @ViewsField("content_lock_break_link")
 */
class ContentLockBreak extends FieldPluginBase {

  /**
   * Prepares link to the file.
   *
   * @param string $data
   *   The XSS safe string for the link text.
   * @param \Drupal\views\ResultRow $values
   *   The values retrieved from a single row of a view's query result.
   *
   * @return string
   *   Returns a string for the link text.
   */
  protected function renderLink($data, ResultRow $values) {
    $entity = $this->getEntity($values);
    $url = Url::fromRoute(
      'content_lock.break_lock.' . $entity->getEntityTypeId(),
      ['entity' => $entity->id()]
    );

    $break_link = Link::fromTextAndUrl('Break lock', $url);
    return $break_link->toString();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    return $this->renderLink($this->sanitizeValue($value), $values);
  }

}
