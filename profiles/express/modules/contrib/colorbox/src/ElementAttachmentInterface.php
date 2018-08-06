<?php

namespace Drupal\colorbox;

/**
 * An interface for attaching things to the built page.
 */
interface ElementAttachmentInterface {

  /**
   * Attach information to the page array.
   *
   * @param array $element
   *   The page array.
   */
  public function attach(array &$element);

  /**
   * Check if the attachment should be added to the current page.
   *
   * @return bool
   *   TRUE if the attachment should be added to the current page.
   */
  public function isApplicable();

}
