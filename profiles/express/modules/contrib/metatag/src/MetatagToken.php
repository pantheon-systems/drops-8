<?php

namespace Drupal\metatag;
use Drupal\Core\Utility\Token;

/**
 * Token handling service. Uses core token service or contributed Token.
 */
class MetatagToken {

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructs a new MetatagToken object.
   *
   * @param \Drupal\Core\Utility\Token $token
   *   Token service.
   */
  public function __construct(Token $token) {
    $this->token = $token;
  }

  /**
   * Wrapper for the Token module's string parsing.
   *
   * @param $string
   * @param $data
   * @param array $options
   *
   * @return mixed|string $string
   */
  public function replace($string, array $data = [], $options = []) {
    // Set default requirements for metatag unless options specify otherwise.
    $options = $options + [
      'clear' => TRUE
    ];

    $replaced = $this->token->replace($string, $data, $options);

    // Ensure that there are no double-slash sequences due to empty token
    // values.
    $replaced = preg_replace('/(?<!:)\/+\//', '/', $replaced);

    return $replaced;
  }

  /**
   * Gatekeeper function to direct to either the core or contributed Token.
   *
   * @param array $token_types
   *   The token types to filter the tokens list by. Defaults to an empty array.
   *
   * @return array
   *   If token module is installed, a popup browser plus a help text. If not
   *   only the help text.
   */
  public function tokenBrowser(array $token_types = []) {
    $form = [];

    $form['intro_text'] = [
      '#markup' => '<p>' . t('<strong>Configure the meta tags below.</strong><br /> To view a summary of the individual meta tags and the pattern for a specific configuration, click on its name below. Use tokens to avoid redundant meta data and search engine penalization. For example, a \'keyword\' value of "example" will be shown on all content using this configuration, whereas using the [node:field_keywords] automatically inserts the "keywords" values from the current entity (node, term, etc).') . '</p>',
    ];

    // Normalize taxonomy tokens.
    if (!empty($token_types)) {
      $token_types = array_map(function($value) {
        return stripos($value, 'taxonomy_') === 0 ? substr($value, strlen('taxonomy_')) : $value;
      }, (array) $token_types);
    }

    $form['tokens'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => $token_types,
      '#global_types' => TRUE,
      '#show_nested' => FALSE,
    ];

    return $form;
  }

}
