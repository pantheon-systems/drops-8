<?php

/**
 * @file
 * Contains \Drupal\linkit_test\Plugin\Linkit\Matcher\DummyMatcher.
 */

namespace Drupal\linkit_test\Plugin\Linkit\Matcher;

use Drupal\linkit\MatcherBase;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * @Matcher(
 *   id = "dummy_matcher",
 *   label = @Translation("Dummy Matcher"),
 * )
 */
class DummyMatcher extends MatcherBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMatches($string) {
    $matches[] = [
      'title' => 'DummyMatcher title',
      'description' => 'DummyMatcher description',
      'path' => 'http://example.com',
      'group' => 'DummyMatcher',
    ];

    return $matches;
  }

}
