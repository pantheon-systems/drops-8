<?php
namespace Drupal\pathauto;

use Drupal\Core\TypedData\TypedData;

/**
 * A property that stores in keyvalue whether an entity should receive an alias.
 */
class PathautoState extends TypedData {

  /**
   * An automatic alias should not be created.
   */
  const SKIP = 0;

  /**
   * An automatic alias should be created.
   */
  const CREATE = 1;

  /**
   * Pathauto state.
   *
   * @var int
   */
  protected $value;

  /**
   * @var \Drupal\Core\Field\FieldItemInterface
   */
  protected $parent;

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    if ($this->value === NULL) {
      // If no value has been set or loaded yet, try to load a value if this
      // entity has already been saved.
      $this->value = \Drupal::keyValue($this->getCollection())
        ->get($this->parent->getEntity()->id());
      // If it was not yet saved or no value was found, then set the flag to
      // create the alias if there is a matching pattern.
      if ($this->value === NULL) {
        $entity = $this->parent->getEntity();
        $pattern = \Drupal::service('pathauto.generator')->getPatternByEntity($entity);
        $this->value = !empty($pattern) ? static::CREATE : static::SKIP;
      }
    }
    return $this->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    $this->value = $value;
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * Returns TRUE if a value was set.
   */
  public function hasValue() {
    return $this->value !== NULL;
  }

  /**
   * Persists the state.
   */
  public function persist() {
    \Drupal::keyValue($this->getCollection())->set(
      $this->parent->getEntity()
        ->id(), $this->value
    );
  }

  /**
   * Deletes the stored state.
   */
  public function purge() {
    \Drupal::keyValue($this->getCollection())
      ->delete($this->parent->getEntity()->id());
  }

  /**
   * Returns the key value collection that should be used for the given entity.
   * @return string
   */
  protected function getCollection() {
    return 'pathauto_state.' . $this->parent->getEntity()->getEntityTypeId();
  }

}
