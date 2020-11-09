<?php

namespace Drupal\Tests\token\Functional\Tree;

use Behat\Mink\Element\NodeElement;

/**
 * Helper trait to assert tokens in token tree browser.
 */
trait TokenTreeTestTrait {

  /**
   * Get an array of token groups from the last retrieved page.
   *
   * @return array
   *   Array of token group names.
   */
  protected function getTokenGroups() {
    $groups = $this->xpath('//tr[contains(@class, "token-group")]/td[1]');
    return array_map(function (NodeElement $item) {
      return (string) $item->getText();
    }, $groups);
  }

  /**
   * Check to see if the specified token group is present in the token browser.
   *
   * @param string $token_group
   *   The name of the token group.
   * @param string $message
   *   (optional) A message to display with the assertion.
   */
  protected function assertTokenGroup($token_group, $message = '') {
    $groups = $this->getTokenGroups();

    if (!$message) {
      $message = "Token group $token_group found.";
    }

    $this->assertTrue(in_array($token_group, $groups), $message);
  }

  /**
   * Check to see if the specified token group is not present in the token
   * browser.
   *
   * @param string $token_group
   *   The name of the token group.
   * @param string $message
   *   (optional) A message to display with the assertion.
   */
  protected function assertTokenNotGroup($token_group, $message = '') {
    $groups = $this->getTokenGroups();

    if (!$message) {
      $message = "Token group $token_group not found.";
    }

    $this->assertFalse(in_array($token_group, $groups), $message);
  }

  /**
   * Check to see if the specified token is present in the token browser.
   *
   * @param $token
   *   The token name with the surrounding square brackets [].
   * @param string $parent
   *   (optional) The parent CSS identifier of this token.
   * @param string $message
   *   (optional) A message to display with the assertion.
   */
  protected function assertTokenInTree($token, $parent = '', $message = '') {
    $xpath = $this->getXpathForTokenInTree($token, $parent);

    if (!$message) {
      $message = "Token $token found.";
    }

    $this->assertCount(1, $this->xpath($xpath), $message);
  }

  /**
   * Check to see if the specified token is present in the token browser.
   *
   * @param $token
   *   The token name with the surrounding square brackets [].
   * @param string $parent
   *   (optional) The parent CSS identifier of this token.
   * @param string $message
   *   (optional) A message to display with the assertion.
   */
  protected function assertTokenNotInTree($token, $parent = '', $message = '') {
    $xpath = $this->getXpathForTokenInTree($token, $parent);

    if (!$message) {
      $message = "Token $token not found.";
    }

    $this->assertCount(0, $this->xpath($xpath), $message);
  }

  /**
   * Get xpath to check for token in tree.
   *
   * @param $token
   *   The token name with the surrounding square brackets [].
   * @param string $parent
   *   (optional) The parent CSS identifier of this token.
   *
   * @return string
   *   The xpath to check for the token and parent.
   */
  protected function getXpathForTokenInTree($token, $parent = '') {
    $xpath = "//tr";
    if ($parent) {
      $xpath .= '[@data-tt-parent-id="token-' . $parent . '"]';
    }
    $xpath .= '/td[contains(@class, "token-key") and text() = "' . $token . '"]';
    return $xpath;
  }
}
