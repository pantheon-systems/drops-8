<?php

namespace Drupal\Tests\video_embed_field\Kernel;

/**
 * Strip whitespace.
 */
trait StripWhitespaceTrait {

  /**
   * Remove HTML whitespace from a string.
   *
   * @param string $string
   *   The input string.
   *
   * @return string
   *   The whitespace cleaned string.
   */
  protected function stripWhitespace($string) {
    $no_whitespace = preg_replace('/\s{2,}/', '', $string);
    $no_whitespace = str_replace("\n", '', $no_whitespace);
    return $no_whitespace;
  }

}
