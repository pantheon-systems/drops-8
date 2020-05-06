<?php

namespace Drupal\webform;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\BubbleableMetadata;

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
   * @param \Drupal\Core\Render\BubbleableMetadata|null $bubbleable_metadata
   *   (optional) An object to which static::generate() and the hooks and
   *   functions that it invokes will add their required bubbleable metadata.
   *
   * @return string|array
   *   Text or array with tokens replaced.
   *
   * @see \Drupal\Core\Utility\Token::replace
   */
  public function replace($text, EntityInterface $entity = NULL, array $data = [], array $options = [], BubbleableMetadata $bubbleable_metadata = NULL);

  /**
   * Replace tokens in text with no render context.
   *
   * This method allows tokens to be replaced when there is no render context
   * via REST and JSON API requests.
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
   *
   * @return string|array
   *   Text or array with tokens replaced.
   *
   * @see \Drupal\Core\Utility\Token::replace
   */
  public function replaceNoRenderContext($text, EntityInterface $entity = NULL, array $data = [], array $options = []);

  /**
   * Build token tree link if token.module is installed.
   *
   * @param array $token_types
   *   An array containing token types that should be shown in the tree.
   *
   * @return array
   *   A render array containing a token tree link.
   */
  public function buildTreeLink(array $token_types = ['webform', 'webform_submission']);

  /**
   * Build token tree element if token.module is installed.
   *
   * @param array $token_types
   *   An array containing token types that should be shown in the tree.
   * @param string $description
   *   (optional) Description to appear after the token tree link.
   *
   * @return array
   *   A render array containing a token tree link wrapped in a div.
   */
  public function buildTreeElement(array $token_types = ['webform', 'webform_submission'], $description = NULL);

  /**
   * Validate form that should have tokens in it.
   *
   * @param array $form
   *   A form.
   * @param array $token_types
   *   An array containing token types that should be validated.
   *
   * @see token_element_validate()
   */
  public function elementValidate(array &$form, array $token_types = ['webform', 'webform_submission', 'webform_handler']);

}
