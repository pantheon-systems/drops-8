<?php

namespace Drupal\webform_access\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webform_access\WebformAccessTypeInterface;

/**
 * Defines the webform access type entity.
 *
 * @ConfigEntityType(
 *   id = "webform_access_type",
 *   label = @Translation("Webform access type"),
 *   label_collection = @Translation("Access types"),
 *   label_singular = @Translation("access type"),
 *   label_plural = @Translation("access types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count access type",
 *     plural = "@count access types",
 *   ),
 *   handlers = {
 *     "storage" = "\Drupal\webform_access\WebformAccessTypeStorage",
 *     "access" = "Drupal\webform_access\WebformAccessTypeAccessControlHandler",
 *     "list_builder" = "Drupal\webform_access\WebformAccessTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\webform_access\WebformAccessTypeForm",
 *       "edit" = "Drupal\webform_access\WebformAccessTypeForm",
 *       "delete" = "Drupal\webform_access\WebformAccessTypeDeleteForm",
 *     }
 *   },
 *   admin_permission = "administer webform",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/webform/config/access/type/manage/add",
 *     "edit-form" = "/admin/structure/webform/config/access/type/manage/{webform_access_type}",
 *     "delete-form" = "/admin/structure/webform/config/access/type/manage/{webform_access_type}/delete",
 *     "collection" = "/admin/structure/webform/config/access/type/manage",
 *   },
 *   config_export = {
 *     "id",
 *     "uuid",
 *     "label",
 *   }
 * )
 */
class WebformAccessType extends ConfigEntityBase implements WebformAccessTypeInterface {

  use StringTranslationTrait;

  /**
   * The webform access type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The webform access type UUID.
   *
   * @var string
   */
  protected $uuid;

  /**
   * The webform access type label.
   *
   * @var string
   */
  protected $label;

}
