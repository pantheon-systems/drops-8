<?php

/**
 * @file
 * Contains \Drupal\linkit\ProfileInterface.
 */

namespace Drupal\linkit;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a profile entity.
 */
interface ProfileInterface extends ConfigEntityInterface {

  /**
   * Gets the profile description.
   *
   * @return string
   *   The profile description.
   */
  public function getDescription();

  /**
   * Sets the profile description.
   *
   * @param string $description
   *   The profile description.
   *
   * @return $this
   */
  public function setDescription($description);

  /**
   * Returns a specific attribute.
   *
   * @param string $attribute_id
   *   The attribute ID.
   *
   * @return \Drupal\linkit\AttributeInterface
   *   The attribute object.
   */
  public function getAttribute($attribute_id);

  /**
   * Returns the attributes for this profile.
   *
   * @return \Drupal\linkit\AttributeCollection|\Drupal\linkit\AttributeInterface[]
   *   The attribute collection.
   */
  public function getAttributes();

  /**
   * Adds an attribute to this profile.
   *
   * @param array $configuration
   *   An array of attribute configuration.
   *
   * @return String
   *   The ID of the attribute.
   */
  public function addAttribute(array $configuration);

  /**
   * Removes an attribute from this profile.
   *
   * @param string $attribute_id
   *  The attribute ID.
   *
   * @return $this
   */
  public function removeAttribute($attribute_id);

  /**
   * Sets the configuration for an attribute instance.
   *
   * @param string $attribute_id
   *   The ID of the attribute to set the configuration for.
   * @param array $configuration
   *   The attribute configuration to set.
   *
   * @return $this
   */
  public function setAttributeConfig($attribute_id, array $configuration);

  /**
   * Returns a specific matcher.
   *
   * @param string $instance_id
   *   The matcher instance ID.
   *
   * @return \Drupal\linkit\MatcherInterface
   *   The matcher object.
   */
  public function getMatcher($instance_id);

  /**
   * Returns the matchers for this profile.
   *
   * @return \Drupal\linkit\MatcherCollection|\Drupal\linkit\MatcherInterface[]
   *   The matcher collection.
   */
  public function getMatchers();

  /**
   * Adds a matcher to this profile.
   *
   * @param array $configuration
   *   An array of matcher configuration.
   *
   * @return string
   *   The instance ID of the matcher.
   */
  public function addMatcher(array $configuration);

  /**
   * Removes a matcher from this profile.
   *
   * @param \Drupal\linkit\MatcherInterface $matcher
   *  The matcher object.
   *
   * @return $this
   */
  public function removeMatcher(MatcherInterface $matcher);

  /**
   * Sets the configuration for a matcher instance.
   *
   * @param string $instance_id
   *   The instance ID of the matcher to set the configuration for.
   * @param array $configuration
   *   The matcher configuration to set.
   *
   * @return $this
   */
  public function setMatcherConfig($instance_id, array $configuration);

}
