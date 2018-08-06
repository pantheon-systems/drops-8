<?php

namespace Drupal\pathauto;

/**
 * Alias types that support batch updates and deletions.
 */
interface AliasTypeBatchUpdateInterface extends AliasTypeInterface {

  /**
   * Gets called to batch update all entries.
   *
   * @param string $action
   *   One of:
   *   - 'create' to generate a URL alias for paths having none.
   *   - 'update' to recreate the URL alias for paths already having one, useful if the pattern changed.
   *   - 'all' to do both actions above at the same time.
   * @param array $context
   *   Batch context.
   */
  public function batchUpdate($action, &$context);

  /**
   * Gets called to batch delete all aliases created by pathauto.
   *
   * @param array $context
   *   Batch context.
   */
  public function batchDelete(&$context);

}
