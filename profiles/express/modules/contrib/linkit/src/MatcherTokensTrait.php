<?php

/**
 * @file
 * Contains \Drupal\linkit\MatcherTokensTrait.
 */

namespace Drupal\linkit;

/**
 * Provides friendly methods for matchers using tokens.
 */
trait MatcherTokensTrait {

  /**
   * Inserts a form element with a list of available tokens.
   *
   * @param $form
   *   The form array to append the token list to.
   * @param array $types
   *   An array of token types to use.
   */
  public function insertTokenList(&$form, array $types = array()) {
    if (\Drupal::moduleHandler()->moduleExists('token')) {
      // Add the token tree UI.
      $form['token_tree'] = array(
        '#theme' => 'token_tree_link',
        '#token_types' => $types,
        '#dialog' => TRUE,
        '#weight' => -90,
      );
    }
    else {
      $token_items = array();
      foreach ($this->getAvailableTokens($types) as $type => $tokens) {
        foreach ($tokens as $name => $info) {
          $token_description = !empty($info['description']) ? $info['description'] : '';
          $token_items[$type . ':' . $name] = "[$type:$name]" . ' - ' . $info['name'] . ': ' . $token_description;
        }
      }

      if (count($token_items)) {
        $form['tokens'] = array(
          '#type' => 'details',
          '#title' => t('Available tokens'),
          '#weight' => -90,
        );

        $form['tokens']['list'] = array(
          '#theme' => 'item_list',
          '#items' => $token_items,
        );
      }
    }
  }

  /**
   * Gets all available tokens.
   *
   * @param array $types
   *   An array of token types to use.
   * @return array
   *   An array with available tokens
   */
  public function getAvailableTokens(array $types = array()) {
    $info = \Drupal::token()->getInfo();
    $available = array_intersect_key($info['tokens'], array_flip($types));
    return $available;
  }

}
