<?php

namespace Drupal\webform;

use Drupal\Core\Entity\EntityInterface;

/**
 * Defines an interface for token manager classes.
 */
interface WebformTokenManagerInterface {

  /**
   * Replace tokens in text.
   *
   * @param string|array $text
   *   A string of text that may contain tokens.
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   A Webform or Webform submission entity.
   * @param array $data
   *   (optional) An array of keyed objects.
   * @param array $options
   *   (optional) A keyed array of settings and flags to control the token
   *   replacement process. Supported options are:
   *   - langcode: A language code to be used when generating locale-sensitive
   *     tokens.
   *   - callback: A callback function that will be used to post-process the
   *     array of token replacements after they are generated.
   *   - clear: A boolean flag indicating that tokens should be removed from the
   *     final text if no replacement value can be generated.
   *   - webform_clear: A boolean flag indicating that only webform tokens
   *     should be removed from the final text if no replacement value can be
   *     generated. (Default is TRUE)
   *
   * @return string|array
   *   Text or array with tokens replaced.
   *
   * @see \Drupal\Core\Utility\Token::replace
   */
  public function replace($text, EntityInterface $entity = NULL, array $data = [], array $options = []);

  /**
   * Build token tree link if token.module is installed.
   *
   * @param array $token_types
   *   An array containing token types that should be shown in the tree.
   * @param string $description
   *   (optional) Description to appear after the token tree link.
   */
  public function buildTreeLink(array $token_types = ['webform', 'webform_submission'], $description = NULL);

}
