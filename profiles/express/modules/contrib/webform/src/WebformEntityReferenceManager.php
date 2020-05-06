<?php

namespace Drupal\webform;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\user\UserDataInterface;
use Drupal\webform\Entity\Webform;

/**
 * Webform entity reference (field) manager.
 *
 * The webform entity reference (field) manager is used to track webforms that
 * are attached to entities, specifically webform nodes. Generally, only one
 * webform is attached to a single node. Field API does allow multiple
 * webforms to be attached to any entity and this services helps handle this
 * edge case.
 */
class WebformEntityReferenceManager implements WebformEntityReferenceManagerInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * Cache of source entity webforms.
   *
   * @var array
   */
  protected $webforms = [];

  /**
   * Cache of source entity field names.
   *
   * @var array
   */
  protected $fieldNames = [];

  /**
   * Constructs a WebformEntityReferenceManager object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   */
  public function __construct(RouteMatchInterface $route_match, AccountInterface $current_user, UserDataInterface $user_data) {
    $this->routeMatch = $route_match;
    $this->currentUser = $current_user;
    $this->userData = $user_data;
  }

  /****************************************************************************/
  // User data methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function isUserWebformRoute(EntityInterface $entity) {
    $entity_type = $entity->getEntityTypeId();
    $route_name = $this->routeMatch->getRouteName();
    $user_routes = [
      "entity.$entity_type.webform.test_form",
      "entity.$entity_type.webform.api_form",
    ];
    return in_array($this->routeMatch->getRouteName(), $user_routes)
      || (strpos($route_name, "entity.$entity_type.webform.results_") === 0);
  }

  /**
   * {@inheritdoc}
   */
  public function setUserWebformId(EntityInterface $entity, $webform_id) {
    $module = 'webform_' . $entity->getEntityTypeId();
    $uid = $this->currentUser->id();
    $name = $entity->id();

    $values = $this->userData->get($module, $uid, $name) ?: [];
    $values['target_id'] = $webform_id;

    $this->userData->set($module, $uid, $name, $values);

  }

  /**
   * {@inheritdoc}
   */
  public function getUserWebformId(EntityInterface $entity) {
    $module = 'webform_' . $entity->getEntityTypeId();
    $uid = $this->currentUser->id();
    $name = $entity->id();

    $values = $this->userData->get($module, $uid, $name) ?: [];

    if (isset($values['target_id'])) {
      $webforms = $this->getWebforms($entity);
      if (isset($webforms[$values['target_id']])) {
        return $values['target_id'];
      }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteUserWebformId(EntityInterface $entity) {
    $module = 'webform_' . $entity->getEntityTypeId();
    $name = $entity->id();

    $this->userData->delete($module, NULL, $name);
  }

  /****************************************************************************/
  // Field methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function hasField(EntityInterface $entity = NULL) {
    return $this->getFieldName($entity) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldName(EntityInterface $entity = NULL) {
    $field_names = $this->getFieldNames($entity);
    return $field_names ? reset($field_names) : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldNames(EntityInterface $entity = NULL) {
    if ($entity === NULL || !method_exists($entity, 'hasField')) {
      return [];
    }

    // Cache the source entity's field names.
    $entity_id = $entity->getEntityTypeId() . '-' . $entity->id();
    if (isset($this->fieldNames[$entity_id])) {
      return $this->fieldNames[$entity_id];
    }

    $field_names = [];
    if ($entity instanceof ContentEntityInterface) {
      $fields = $entity->getFieldDefinitions();
      foreach ($fields as $field_name => $field_definition) {
        if ($field_definition->getType() == 'webform') {
          $field_names[$field_name] = $field_name;
        }
      }
    }

    // Sort fields alphabetically.
    ksort($field_names);

    $this->fieldNames[$entity_id] = $field_names;
    return $field_names;
  }

  /**
   * {@inheritdoc}
   */
  public function getWebform(EntityInterface $entity = NULL) {
    if ($webform_id = $this->getUserWebformId($entity)) {
      return Webform::load($webform_id);
    }
    elseif ($webforms = $this->getWebforms($entity)) {
      return reset($webforms);
    }
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getWebforms(EntityInterface $entity = NULL) {
    // Cache the source entity's webforms.
    $entity_id = $entity->getEntityTypeId() . '-' . $entity->id();
    if (isset($this->webforms[$entity_id])) {
      return $this->webforms[$entity_id];
    }

    $field_names = $this->getFieldNames($entity);
    $target_entities = [];
    $sorted_entities = [];
    foreach ($field_names as $field_name) {
      foreach ($entity->$field_name as $item) {
        $sorted_entities[$item->target_id] = (method_exists($item->entity, 'getWeight')) ? $item->entity->getWeight() : 0;
        $target_entities[$item->target_id] = $item->entity;
      }
    }
    // Sort the webforms by key and then weight.
    ksort($sorted_entities);
    asort($sorted_entities, SORT_NUMERIC);

    // Return the sort webforms.
    $webforms = [];
    foreach (array_keys($sorted_entities) as $target_id) {
      $webforms[$target_id] = $target_entities[$target_id];
    }

    $this->webforms[$entity_id] = $webforms;
    return $webforms;
  }

  /****************************************************************************/
  // Table methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getTableNames() {
    /** @var \Drupal\field\FieldStorageConfigInterface[] $field_storage_configs */
    $field_storage_configs = FieldStorageConfig::loadMultiple();
    $tables = [];
    foreach ($field_storage_configs as $field_storage_config) {
      if ($field_storage_config->getType() == 'webform') {
        $webform_field_table = $field_storage_config->getTargetEntityTypeId();
        $webform_field_name = $field_storage_config->getName();
        $tables[$webform_field_table . '__' . $webform_field_name] = $webform_field_name;
        $tables[$webform_field_table . '_revision__' . $webform_field_name] = $webform_field_name;
      };
    }
    return $tables;
  }

}
