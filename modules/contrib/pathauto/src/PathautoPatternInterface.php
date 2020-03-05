<?php

namespace Drupal\pathauto;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Pathauto pattern entities.
 */
interface PathautoPatternInterface extends ConfigEntityInterface {

  /**
   * Get the tokenized pattern used during alias generation.
   *
   * @return string
   */
  public function getPattern();

  /**
   * Set the tokenized pattern to use during alias generation.
   *
   * @param string $pattern
   *
   * @return $this
   */
  public function setPattern($pattern);

  /**
   * Gets the type of this pattern.
   *
   * @return string
   */
  public function getType();

  /**
   * @return \Drupal\pathauto\AliasTypeInterface
   */
  public function getAliasType();

  /**
   * Gets the weight of this pattern (compared to other patterns of this type).
   *
   * @return int
   */
  public function getWeight();

  /**
   * Sets the weight of this pattern (compared to other patterns of this type).
   *
   * @param int $weight
   *   The weight of the variant.
   *
   * @return $this
   */
  public function setWeight($weight);

  /**
   * Returns the contexts of this pattern.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface[]
   */
  public function getContexts();

  /**
   * Returns whether a relationship exists.
   *
   * @param string $token
   *   Relationship identifier.
   *
   * @return bool
   *   TRUE if the relationship exists, FALSE otherwise.
   */
  public function hasRelationship($token);

  /**
   * Adds a relationship.
   *
   * The relationship will not be changed if it already exists.
   *
   * @param string $token
   *   Relationship identifier.
   * @param string|null $label
   *   (optional) A label, will use the label of the referenced context if not
   *   provided.
   *
   * @return $this
   */
  public function addRelationship($token, $label = NULL);

  /**
   * Replaces a relationship.
   *
   * Only already existing relationships are updated.
   *
   * @param string $token
   *   Relationship identifier.
   * @param string|null $label
   *   (optional) A label, will use the label of the referenced context if not
   *   provided.
   *
   * @return $this
   */
  public function replaceRelationship($token, $label);

  /**
   * Removes a relationship.
   *
   * @param string $token
   *   Relationship identifier.
   *
   * @return $this
   */
  public function removeRelationship($token);

  /**
   * Returns a list of relationships.
   *
   * @return array[]
   *   Keys are context tokens, and values are arrays with the following keys:
   *   - label (string|null, optional): The human-readable label of this
   *     relationship.
   */
  public function getRelationships();

  /**
   * Gets the selection condition collection.
   *
   * @return \Drupal\Core\Condition\ConditionInterface[]|\Drupal\Core\Condition\ConditionPluginCollection
   */
  public function getSelectionConditions();

  /**
   * Adds selection criteria.
   *
   * @param array $configuration
   *   Configuration of the selection criteria.
   *
   * @return string
   *   The condition id of the new criteria.
   */
  public function addSelectionCondition(array $configuration);

  /**
   * Gets selection criteria by condition id.
   *
   * @param string $condition_id
   *   The id of the condition.
   *
   * @return \Drupal\Core\Condition\ConditionInterface
   */
  public function getSelectionCondition($condition_id);

  /**
   * Removes selection criteria by condition id.
   *
   * @param string $condition_id
   *   The id of the condition.
   *
   * @return $this
   */
  public function removeSelectionCondition($condition_id);

  /**
   * Gets the selection logic used by the criteria (ie. "and" or "or").
   *
   * @return string
   *   Either "and" or "or"; represents how the selection criteria are combined.
   */
  public function getSelectionLogic();

  /**
   * Determines if this pattern can apply a given object.
   *
   * @param $object
   *   The object used to determine if this plugin can apply.
   *
   * @return bool
   */
  public function applies($object);

}
