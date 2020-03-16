<?php

namespace Drupal\metatag\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\metatag\MetatagDefaultsInterface;

/**
 * Defines the Metatag defaults entity.
 *
 * @ConfigEntityType(
 *   id = "metatag_defaults",
 *   label = @Translation("Metatag defaults"),
 *   handlers = {
 *     "list_builder" = "Drupal\metatag\MetatagDefaultsListBuilder",
 *     "form" = {
 *       "add" = "Drupal\metatag\Form\MetatagDefaultsForm",
 *       "edit" = "Drupal\metatag\Form\MetatagDefaultsForm",
 *       "delete" = "Drupal\metatag\Form\MetatagDefaultsDeleteForm",
 *       "revert" = "Drupal\metatag\Form\MetatagDefaultsRevertForm"
 *     }
 *   },
 *   config_prefix = "metatag_defaults",
 *   admin_permission = "administer meta tags",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/search/metatag/{metatag_defaults}/edit",
 *     "delete-form" = "/admin/config/search/metatag/{metatag_defaults}/delete",
 *     "revert-form" = "/admin/config/search/metatag/{metatag_defaults}/revert",
 *     "collection" = "/admin/config/search/metatag"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "tags"
 *   }
 * )
 */
class MetatagDefaults extends ConfigEntityBase implements MetatagDefaultsInterface {

  /**
   * The Metatag defaults ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Metatag defaults label.
   *
   * @var string
   */
  protected $label;

  /**
   * The default tag values.
   *
   * @var array
   */
  protected $tags = [];

  /**
   * Returns TRUE if a tag exists.
   *
   * @param string $tag_id
   *   The identifier of the tag.
   *
   * @return bool
   *   TRUE if the tag exists.
   */
  public function hasTag($tag_id) {
    return array_key_exists($tag_id, $this->tags);
  }

  /**
   * Returns the value of a tag.
   *
   * @param string $tag_id
   *   The identifier of the tag.
   *
   * @return array|null
   *   Array containing the tag values or NULL if not found.
   */
  public function getTag($tag_id) {
    if (!$this->hasTag($tag_id)) {
      return NULL;
    }
    return $this->tags[$tag_id];
  }

  /**
   * Reverts an entity to its default values.
   */
  public function revert() {
    $default_install_path = drupal_get_path('module', 'metatag') . '/' . InstallStorage::CONFIG_INSTALL_DIRECTORY;
    $storage = new FileStorage($default_install_path, StorageInterface::DEFAULT_COLLECTION);
    $default_config_data = $storage->read('metatag.metatag_defaults.' . $this->id());
    if ($default_config_data) {
      $this->set('tags', $default_config_data['tags']);
      $this->save();
    }
  }

  /**
   * Overwrite the current tags with new values.
   */
  public function overwriteTags(array $new_tags = []) {
    if (!empty($new_tags)) {
      // Get the existing tags.
      $combined_tags = $this->get('tags');

      // Loop over the new tags, adding them to the existing tags.
      foreach ($new_tags as $tag_name => $data) {
        $combined_tags[$tag_name] = $data;
      }

      // Save the combination of the existing tags + the new tags.
      $this->set('tags', $combined_tags);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b) {
    // Put always Global in 1st place and front page later if available.
    if ($a->id() == 'global') {
      return -1;
    }
    elseif ($b->id() == 'global') {
      return 1;
    }
    elseif ($a->id() == 'front') {
      return -1;
    }
    elseif ($b->id() == 'front') {
      return 1;
    }

    // Use the default sort function.
    return parent::sort($a, $b);
  }

}
