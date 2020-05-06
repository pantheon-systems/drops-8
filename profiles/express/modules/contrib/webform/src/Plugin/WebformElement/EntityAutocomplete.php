<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\Plugin\WebformElementEntityReferenceInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'entity_autocomplete' element.
 *
 * @WebformElement(
 *   id = "entity_autocomplete",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Entity!Element!EntityAutocomplete.php/class/EntityAutocomplete",
 *   label = @Translation("Entity autocomplete"),
 *   description = @Translation("Provides a form element to select an entity reference using an autocompletion."),
 *   category = @Translation("Entity reference elements"),
 * )
 */
class EntityAutocomplete extends WebformElementBase implements WebformElementEntityReferenceInterface {

  use WebformEntityReferenceTrait;

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      // Entity reference settings.
      'target_type' => '',
      'selection_handler' => 'default',
      'selection_settings' => [],
      'tags' => FALSE,
    ] + parent::defineDefaultProperties()
      + $this->defineDefaultMultipleProperties();
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function setDefaultValue(array &$element) {
    // Make sure tags or multiple is used.
    if (!empty($element['#tags']) && isset($element['#multiple'])) {
      unset($element['#multiple']);
    }

    if (isset($element['#multiple'])) {
      $element['#default_value'] = (isset($element['#default_value'])) ? (array) $element['#default_value'] : NULL;
      return;
    }

    if (!empty($element['#default_value'])) {
      $target_type = $this->getTargetType($element);
      $entity_storage = $this->entityTypeManager->getStorage($target_type);
      if ($entities = $entity_storage->loadMultiple((array) $element['#default_value'])) {
        $element['#default_value'] = (empty($element['#tags'])) ? reset($entities) : $entities;
      }
      else {
        $element['#default_value'] = NULL;
      }
    }
    else {
      $element['#default_value'] = NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function supportsMultipleValues() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasMultipleWrapper() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasMultipleValues(array $element) {
    if ($this->hasProperty('tags') && isset($element['#tags'])) {
      return $element['#tags'];
    }
    else {
      return parent::hasMultipleValues($element);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    // Remove maxlength.
    $element['#maxlength'] = NULL;

    // If selection handler include auto_create when need to also set it for
    // the $element.
    // @see \Drupal\Core\Entity\Element\EntityAutocomplete::validateEntityAutocomplete
    if (!empty($element['#selection_settings']['auto_create'])
      && !empty($element['#selection_settings']['auto_create_bundle'])) {
      $element['#autocreate']['bundle'] = $element['#selection_settings']['auto_create_bundle'];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareElementValidateCallbacks(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepareElementValidateCallbacks($element, $webform_submission);
    $element['#element_validate'][] = ['\Drupal\webform\Plugin\WebformElement\EntityAutocomplete', 'validateEntityAutocomplete'];
  }

  /**
   * Form API callback. Remove target id property and create an array of entity ids.
   */
  public static function validateEntityAutocomplete(array &$element, FormStateInterface $form_state) {
    // Must use ::getValue($element['#parents']) because $element['#value'] is
    // not being updated.
    // @see \Drupal\Core\Entity\Element\EntityAutocomplete::validateEntityAutocomplete
    $value = $form_state->getValue($element['#parents']);
    if (empty($value) || !is_array($value)) {
      return;
    }

    if (empty($element['#webform_multiple'])) {
      $form_state->setValueForElement($element, static::getEntityIdFromItem($value));
    }
    else {
      $entity_ids = [];
      foreach ($value as $item) {
        $entity_ids[] = static::getEntityIdFromItem($item);
      }
      $form_state->setValueForElement($element, $entity_ids);
    }
  }

  /**
   * Get the entity id from the submitted and processed #value.
   *
   * @param array|string $item
   *   The entity item.
   *
   * @return string
   *   The entity id.
   */
  protected static function getEntityIdFromItem($item) {
    if (isset($item['target_id'])) {
      return $item['target_id'];
    }
    elseif (isset($item['entity'])) {
      // If #auto_create is set then we need to save the entity and get
      // the new entity's id.
      // @todo Decide what level of access controls are needed to allow users to create entities.
      $entity = $item['entity'];
      $entity->save();
      return $entity->id();
    }
    else {
      return $item;
    }
  }

}
