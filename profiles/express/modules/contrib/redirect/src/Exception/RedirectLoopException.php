<?php

namespace Drupal\redirect\Exception;

use Drupal\Component\Utility\SafeMarkup;

/**
 * Exception for when a redirect loop is detected.
 */
class RedirectLoopException extends \RuntimeException {

  /**
   * Formats a redirect loop exception message.
   *
   * @param string $path
   *   The path that results in a redirect loop.
   * @param int $rid
   *   The redirect ID that is involved in a loop.
   */
  public function __construct($path, $rid) {
    parent::__construct(SafeMarkup::format('Redirect loop identified at %path for redirect %rid', ['%path' => $path, '%rid' => $rid]));
  }

}
