<?php

/**
 * @file
 * Contains \Drupal\linkit\ResultManager.
 */

namespace Drupal\linkit;


use Drupal\Component\Utility\Html;
use Drupal\Core\Url;

/**
 * Result service to handle autocomplete matcher results.
 */
class ResultManager {

  /**
   * Gets the results.
   *
   * @param ProfileInterface $linkitProfile
   *   The linkit profile.
   * @param $search_string
   *   The string ro use in the matchers.
   *
   * @return array
   *   An array of matches.
   */
  public function getResults(ProfileInterface $linkitProfile, $search_string) {
    $matches = array();

    if (empty(trim($search_string))) {
      return [[
        'title' => t('No results'),
      ]];
    }

    // Special for link to front page.
    if (strpos($search_string, 'front') !== FALSE) {
      $matches[] = [
        'title' => t('Front page'),
        'description' => 'The front page for this site.',
        'path' => Url::fromRoute('<front>')->toString(),
        'group' => t('System'),
      ];
    }

    foreach ($linkitProfile->getMatchers() as $plugin) {
      $matches = array_merge($matches, $plugin->getMatches($search_string));
    }

    // Check for an e-mail address then return an e-mail match and create a
    // mail-to link if appropriate.
    if (filter_var($search_string, FILTER_VALIDATE_EMAIL)) {
      $matches[] = [
        'title' => t('E-mail @email', ['@email' => $search_string]),
        'description' => t('Opens your mail client ready to e-mail @email', ['@email' => $search_string]),
        'path' => 'mailto:' . Html::escape($search_string),
        'group' => t('E-mail'),
      ];
    }

    // If there is still no matches, return a "no results" array.
    if (empty($matches)) {
      return [[
        'title' => t('No results'),
      ]];
    }

    return $matches;
  }

}
