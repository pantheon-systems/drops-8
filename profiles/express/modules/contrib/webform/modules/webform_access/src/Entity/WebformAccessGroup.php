<?php

namespace Drupal\webform_access\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webform_access\WebformAccessGroupInterface;

/**
 * Defines the webform access group entity.
 *
 * @ConfigEntityType(
 *   id = "webform_access_group",
 *   label = @Translation("Webform access group"),
 *   label_collection = @Translation("Access groups"),
 *   label_singular = @Translation("access group"),
 *   label_plural = @Translation("access groups"),
 *   label_count = @PluralTranslation(
 *     singular = "@count access group",
 *     plural = "@count access groups",
 *   ),
 *   handlers = {
 *     "storage" = "\Drupal\webform_access\WebformAccessGroupStorage",
 *     "access" = "Drupal\webform_access\WebformAccessGroupAccessControlHandler",
 *     "list_builder" = "Drupal\webform_access\WebformAccessGroupListBuilder",
 *     "form" = {
 *       "add" = "Drupal\webform_access\WebformAccessGroupForm",
 *       "edit" = "Drupal\webform_access\WebformAccessGroupForm",
 *       "duplicate" = "Drupal\webform_access\WebformAccessGroupForm",
 *       "delete" = "Drupal\webform_access\WebformAccessGroupDeleteForm",
 *     }
 *   },
 *   admin_permission = "administer webform",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/webform/config/access/group/manage/add",
 *     "edit-form" = "/admin/structure/webform/config/access/group/manage/{webform_access_group}",
 *     "duplicate-form" = "/admin/structure/webform/config/access/group/manage/{webform_access_group}/duplicate",
 *     "delete-form" = "/admin/structure/webform/config/access/group/manage/{webform_access_group}/delete",
 *     "collection" = "/admin/structure/webform/config/access/group/manage",
 *   },
 *   config_export = {
 *     "id",
 *     "uuid",
 *     "label",
 *     "description",
 *     "type",
 *     "permissions",
 *     "emails",
 *   }
 * )
 */
class WebformAccessGroup extends ConfigEntityBase implements WebformAccessGroupInterface {

  use StringTranslationTrait;

  /**
   * The webform access group ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The webform access group UUID.
   *
   * @var string
   */
  protected $uuid;

  /**
   * The webform access group label.
   *
   * @var string
   */
  protected $label;

  /**
   * The webform access group description.
   *
   * @var string
   */
  protected $description;

  /**
   * The webform access group type.
   *
   * @var string
   */
  protected $type;

  /**
   * The webform access group permissions.
   *
   * @var array
   */
  protected $permissions = [];

  /**
   * The webform access group admin user ids.
   *
   * @var array
   */
  protected $adminIds = [];

  /**
   * The webform access group user ids.
   *
   * @var array
   */
  protected $userIds = [];

  /**
   * The webform access group source entity ids.
   *
   * @var array
   */
  protected $entityIds = [];

  /**
   * The webform access group emails.
   *
   * @var array
   */
  protected $emails = [];

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->type ?: '';
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeLabel() {
    if (empty($this->type)) {
      return '';
    }

    $webform_access_type = WebformAccessType::load($this->type);
    return ($webform_access_type) ? $webform_access_type->label() : '';
  }

  /**
   * {@inheritdoc}
   */
  public function setAdminIds(array $uids) {
    $this->adminIds = $uids;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminIds() {
    return $this->adminIds;
  }

  /**
   * {@inheritdoc}
   */
  public function setUserIds(array $uids) {
    $this->userIds = $uids;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserIds() {
    return $this->userIds;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityIds(array $entity_ids) {
    $this->entityIds = $entity_ids;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityIds() {
    return $this->entityIds;
  }

  /**
   * {@inheritdoc}
   */
  public function setEmails(array $emails) {
    $this->emails = $emails;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEmails() {
    return $this->emails;
  }

  /****************************************************************************/
  // Add/Remote methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function addAdminId($uid) {
    if (!in_array($uid, $this->adminIds)) {
      $this->adminIds[] = $uid;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeAdminId($uid) {
    foreach ($this->adminIds as $index => $adminId) {
      if ($adminId == $uid) {
        unset($this->adminIds[$index]);
      }
    }
    $this->adminIds = array_values($this->adminIds);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addUserId($uid) {
    if (!in_array($uid, $this->userIds)) {
      $this->userIds[] = $uid;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeUserId($uid) {
    foreach ($this->userIds as $index => $userId) {
      if ($userId == $uid) {
        unset($this->userIds[$index]);
      }
    }
    $this->userIds = array_values($this->userIds);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addEntityId($entity_type, $entity_id, $field_name, $webform_id) {
    $entity = "$entity_type:$entity_id:$field_name:$webform_id";
    if (!in_array($entity, $this->entityIds)) {
      $this->entityIds[] = $entity;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeEntityId($entity_type, $entity_id, $field_name, $webform_id) {
    $entity = "$entity_type:$entity_id:$field_name:$webform_id";
    foreach ($this->entityIds as $index => $entityId) {
      if ($entity == $entityId) {
        unset($this->entityIds[$index]);
      }
    }
    $this->entityIds = array_values($this->entityIds);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addEmail($email) {
    if (!in_array($email, $this->emails)) {
      $this->emails[] = $email;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeEmail($email) {
    foreach ($this->emails as $index => $email_address) {
      if ($email_address == $email) {
        unset($this->emails[$index]);
      }
    }
    $this->emails = array_values($this->emails);
    return $this;
  }

  /****************************************************************************/
  // Caching methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function invalidateTags() {
    Cache::invalidateTags($this->getCacheTagsToInvalidate());
  }

}
