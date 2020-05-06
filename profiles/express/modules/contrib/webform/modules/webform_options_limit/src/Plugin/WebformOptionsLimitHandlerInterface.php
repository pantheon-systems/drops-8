<?php

namespace Drupal\webform_options_limit\Plugin;

use Drupal\Core\Entity\EntityInterface;
use Drupal\webform\Plugin\WebformHandlerInterface;

/**
 * Defines the interface for webform options limit handlers.
 */
interface WebformOptionsLimitHandlerInterface extends WebformHandlerInterface {

  /**
   * Set the webform source entity.
   *
   * Allows source entity to be injected for building the summary table.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   A source entity.
   *
   * @return $this
   *   This webform handler.
   */
  public function setSourceEntity(EntityInterface $source_entity = NULL);

  /**
   * Get the webform source entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   A source entity.
   */
  public function getSourceEntity();

  /**
   * Build summary table.
   *
   * @return array
   *   A renderable containing the options limit summary table.
   */
  public function buildSummaryTable();

}
