<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Element\EntityAutocomplete.
 */

namespace Drupal\Core\Entity\Element;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Textfield;
use Drupal\Core\Site\Settings;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an entity autocomplete form element.
 *
 * The #default_value accepted by this element is either an entity object or an
 * array of entity objects.
 *
 * @FormElement("entity_autocomplete")
 */
class EntityAutocomplete extends Textfield {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $class = get_class($this);

    // Apply default form element properties.
    $info['#target_type'] = NULL;
    $info['#selection_handler'] = 'default';
    $info['#selection_settings'] = array();
    $info['#tags'] = FALSE;
    $info['#autocreate'] = NULL;
    // This should only be set to FALSE if proper validation by the selection
    // handler is performed at another level on the extracted form values.
    $info['#validate_reference'] = TRUE;
    // IMPORTANT! This should only be set to FALSE if the #default_value
    // property is processed at another level (e.g. by a Field API widget) and
    // it's value is properly checked for access.
    $info['#process_default_value'] = TRUE;

    $info['#element_validate'] = array(array($class, 'validateEntityAutocomplete'));
    array_unshift($info['#process'], array($class, 'processEntityAutocomplete'));

    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    // Process the #default_value property.
    if ($input === FALSE && isset($element['#default_value']) && $element['#process_default_value']) {
      if (is_array($element['#default_value']) && $element['#tags'] !== TRUE) {
        throw new \InvalidArgumentException('The #default_value property is an array but the form element does not allow multiple values.');
      }
      elseif (!is_array($element['#default_value'])) {
        // Convert the default value into an array for easier processing in
        // static::getEntityLabels().
        $element['#default_value'] = array($element['#default_value']);
      }

      if ($element['#default_value'] && !(reset($element['#default_value']) instanceof EntityInterface)) {
        throw new \InvalidArgumentException('The #default_value property has to be an entity object or an array of entity objects.');
      }

      // Extract the labels from the passed-in entity objects, taking access
      // checks into account.
      return static::getEntityLabels($element['#default_value']);
    }
  }

  /**
   * Adds entity autocomplete functionality to a form element.
   *
   * @param array $element
   *   The form element to process. Properties used:
   *   - #target_type: The ID of the target entity type.
   *   - #selection_handler: The plugin ID of the entity reference selection
   *     handler.
   *   - #selection_settings: An array of settings that will be passed to the
   *     selection handler.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The form element.
   *
   * @throws \InvalidArgumentException
   *   Exception thrown when the #target_type or #autocreate['bundle'] are
   *   missing.
   */
  public static function processEntityAutocomplete(array &$element, FormStateInterface $form_state, array &$complete_form) {
    // Nothing to do if there is no target entity type.
    if (empty($element['#target_type'])) {
      throw new \InvalidArgumentException('Missing required #target_type parameter.');
    }

    // Provide default values and sanity checks for the #autocreate parameter.
    if ($element['#autocreate']) {
      if (!isset($element['#autocreate']['bundle'])) {
        throw new \InvalidArgumentException("Missing required #autocreate['bundle'] parameter.");
      }
      // Default the autocreate user ID to the current user.
      $element['#autocreate']['uid'] = isset($element['#autocreate']['uid']) ? $element['#autocreate']['uid'] : \Drupal::currentUser()->id();
    }

    // Store the selection settings in the key/value store and pass a hashed key
    // in the route parameters.
    $selection_settings = isset($element['#selection_settings']) ? $element['#selection_settings'] : [];
    $data = serialize($selection_settings) . $element['#target_type'] . $element['#selection_handler'];
    $selection_settings_key = Crypt::hmacBase64($data, Settings::getHashSalt());

    $key_value_storage = \Drupal::keyValue('entity_autocomplete');
    if (!$key_value_storage->has($selection_settings_key)) {
      $key_value_storage->set($selection_settings_key, $selection_settings);
    }

    $element['#autocomplete_route_name'] = 'system.entity_autocomplete';
    $element['#autocomplete_route_parameters'] = array(
      'target_type' => $element['#target_type'],
      'selection_handler' => $element['#selection_handler'],
      'selection_settings_key' => $selection_settings_key,
    );

    return $element;
  }

  /**
   * Form element validation handler for entity_autocomplete elements.
   */
  public static function validateEntityAutocomplete(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $value = NULL;
    if (!empty($element['#value'])) {
      $options = array(
        'target_type' => $element['#target_type'],
        'handler' => $element['#selection_handler'],
        'handler_settings' => $element['#selection_settings'],
      );
      $handler = \Drupal::service('plugin.manager.entity_reference_selection')->getInstance($options);
      $autocreate = (bool) $element['#autocreate'];

      $input_values = $element['#tags'] ? Tags::explode($element['#value']) : array($element['#value']);
      foreach ($input_values as $input) {
        $match = static::extractEntityIdFromAutocompleteInput($input);
        if ($match === NULL) {
          // Try to get a match from the input string when the user didn't use
          // the autocomplete but filled in a value manually.
          $match = $handler->validateAutocompleteInput($input, $element, $form_state, $complete_form, !$autocreate);
        }

        if ($match !== NULL) {
          $value[] = array(
            'target_id' => $match,
          );
        }
        elseif ($autocreate) {
          // Auto-create item. See an example of how this is handled in
          // \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem::presave().
          $value[] = array(
            'entity' => static::createNewEntity($element['#target_type'], $element['#autocreate']['bundle'], $input, $element['#autocreate']['uid'])
          );
        }
      }

      // Check that the referenced entities are valid, if needed.
      if ($element['#validate_reference'] && !$autocreate && !empty($value)) {
        $ids = array_reduce($value, function ($return, $item) {
          if (isset($item['target_id'])) {
            $return[] = $item['target_id'];
          }
          return $return;
        });

        if ($ids) {
          $valid_ids = $handler->validateReferenceableEntities($ids);
          if ($invalid_ids = array_diff($ids, $valid_ids)) {
            foreach ($invalid_ids as $invalid_id) {
              $form_state->setError($element, t('The referenced entity (%type: %id) does not exist.', array('%type' => $element['#target_type'], '%id' => $invalid_id)));
            }
          }
        }
      }

      // Use only the last value if the form element does not support multiple
      // matches (tags).
      if (!$element['#tags'] && !empty($value)) {
        $last_value = $value[count($value) - 1];
        $value = isset($last_value['target_id']) ? $last_value['target_id'] : $last_value;
      }
    }

    $form_state->setValueForElement($element, $value);
  }

  /**
   * Converts an array of entity objects into a string of entity labels.
   *
   * This method is also responsible for checking the 'view' access on the
   * passed-in entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   An array of entity objects.
   *
   * @return string
   *   A string of entity labels separated by commas.
   */
  public static function getEntityLabels(array $entities) {
    $entity_labels = array();
    foreach ($entities as $entity) {
      $label = ($entity->access('view')) ? $entity->label() : t('- Restricted access -');

      // Take into account "autocreated" entities.
      if (!$entity->isNew()) {
        $label .= ' (' . $entity->id() . ')';
      }

      // Labels containing commas or quotes must be wrapped in quotes.
      $entity_labels[] = Tags::encode($label);
    }

    return implode(', ', $entity_labels);
  }

  /**
   * Extracts the entity ID from the autocompletion result.
   *
   * @param string $input
   *   The input coming from the autocompletion result.
   *
   * @return mixed|null
   *   An entity ID or NULL if the input does not contain one.
   */
  public static function extractEntityIdFromAutocompleteInput($input) {
    $match = NULL;

    // Take "label (entity id)', match the ID from parenthesis when it's a
    // number.
    if (preg_match("/.+\((\d+)\)/", $input, $matches)) {
      $match = $matches[1];
    }
    // Match the ID when it's a string (e.g. for config entity types).
    elseif (preg_match("/.+\(([\w.]+)\)/", $input, $matches)) {
      $match = $matches[1];
    }

    return $match;
  }

  /**
   * Creates a new entity from a label entered in the autocomplete input.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle name.
   * @param string $label
   *   The entity label.
   * @param int $uid
   *   The entity owner ID.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  protected static function createNewEntity($entity_type_id, $bundle, $label, $uid) {
    $entity_manager = \Drupal::entityManager();

    $entity_type = $entity_manager->getDefinition($entity_type_id);
    $bundle_key = $entity_type->getKey('bundle');
    $label_key = $entity_type->getKey('label');

    $entity = $entity_manager->getStorage($entity_type_id)->create(array(
      $bundle_key => $bundle,
      $label_key => $label,
    ));

    if ($entity instanceof EntityOwnerInterface) {
      $entity->setOwnerId($uid);
    }

    return $entity;
  }

}
