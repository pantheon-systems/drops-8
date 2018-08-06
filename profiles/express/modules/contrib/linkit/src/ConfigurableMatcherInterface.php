<?php

/**
 * @file
 * Contains \Drupal\linkit\ConfigurableMatcherInterface.
 */


namespace Drupal\linkit;

use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines the interface for configurable matchers.
 *
 * @see \Drupal\linkit\Annotation\Matcher
 * @see \Drupal\linkit\ConfigurableMatcherBase
 * @see \Drupal\linkit\MatcherInterface
 * @see \Drupal\linkit\MatcherBase
 * @see \Drupal\linkit\MatcherManager
 * @see plugin_api
 */
interface ConfigurableMatcherInterface extends MatcherInterface, PluginFormInterface {
}
