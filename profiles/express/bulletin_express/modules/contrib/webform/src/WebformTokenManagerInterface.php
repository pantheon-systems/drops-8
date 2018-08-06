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
   *   replacement process.
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
   */
  public function buildTreeLink(array $token_types = ['webform', 'webform_submission']);

}
