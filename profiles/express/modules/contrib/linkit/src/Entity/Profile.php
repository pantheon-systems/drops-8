<?php

/**
 * @file
 * Contains \Drupal\linkit\Entity\Profile.
 */

namespace Drupal\linkit\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\linkit\AttributeCollection;
use Drupal\linkit\MatcherCollection;
use Drupal\linkit\MatcherInterface;
use Drupal\linkit\ProfileInterface;

/**
 * Defines the linkit profile entity.
 *
 * @ConfigEntityType(
 *   id = "linkit_profile",
 *   label = @Translation("Linkit profile"),
 *   handlers = {
 *     "list_builder" = "Drupal\linkit\ProfileListBuilder",
 *     "form" = {
 *       "add" = "Drupal\linkit\Form\Profile\AddForm",
 *       "edit" = "Drupal\linkit\Form\Profile\EditForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer linkit profiles",
 *   config_prefix = "linkit_profile",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "collection" = "/admin/config/content/linkit",
 *     "edit-form" = "/admin/config/content/linkit/manage/{linkit_profile}",
 *     "delete-form" = "/admin/config/content/linkit/manage/{linkit_profile}/delete"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "attributes",
 *     "matchers"
 *   }
 * )
 */
class Profile extends ConfigEntityBase implements ProfileInterface, EntityWithPluginCollectionInterface {

  /**
   * The ID of this profile.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable label of this profile.
   *
   * @var string
   */
  protected $label;

  /**
   * Description of this profile.
   *
   * @var string
   */
  protected $description;

  /**
   * Configured attribute for this profile.
   *
   * An associative array of attribute assigned to the profile, keyed by the
   * attribute id of each attribute and using the properties:
   * - id: The plugin ID of the attribute instance.
   * - status: (optional) A Boolean indicating whether the attribute is enabled
   *   in the profile. Defaults to FALSE.
   * - weight: (optional) The weight of the attribute in the profile.
   *   Defaults to 0.
   *
   * @var array
   */
  protected $attributes = [];

  /**
   * Holds the collection of attributes that are attached to this profile.
   *
   * @var \Drupal\linkit\AttributeCollection
   */
  protected $attributeCollection;

  /**
   * Configured matchers for this profile.
   *
   * An associative array of matchers assigned to the profile, keyed by the
   * matcher ID of each matcher and using the properties:
   * - id: The plugin ID of the matchers instance.
   * - status: (optional) A Boolean indicating whether the matchers is enabled
   *   in the profile. Defaults to FALSE.
   * - weight: (optional) The weight of the matchers in the profile.
   *   Defaults to 0.
   *
   * @var array
   */
  protected $matchers = [];

  /**
   * Holds the collection of matchers that are attached to this profile.
   *
   * @var \Drupal\linkit\MatcherCollection
   */
  protected $matcherCollection;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->get('description');
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->set('description', trim($description));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttribute($attribute_id) {
    return $this->getAttributes()->get($attribute_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getAttributes() {
    if (!$this->attributeCollection) {
      $this->attributeCollection = new AttributeCollection($this->getAttributeManager(), $this->attributes);
      $this->attributeCollection->sort();
    }
    return $this->attributeCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function addAttribute(array $configuration) {
    $this->getAttributes()->addInstanceId($configuration['id'], $configuration);
    return $configuration['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function removeAttribute($attribute_id) {
    unset($this->attributes[$attribute_id]);
    $this->getAttributes()->removeInstanceId($attribute_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setAttributeConfig($attribute_id, array $configuration) {
    $this->attributes[$attribute_id] = $configuration;
    $this->getAttributes()->setInstanceConfiguration($attribute_id, $configuration);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMatcher($instance_id) {
    return $this->getMatchers()->get($instance_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getMatchers() {
    if (!$this->matcherCollection) {
      $this->matcherCollection = new MatcherCollection($this->getMatcherManager(), $this->matchers);
      $this->matcherCollection->sort();
    }
    return $this->matcherCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function addMatcher(array $configuration) {
    $configuration['uuid'] = $this->uuidGenerator()->generate();
    $this->getMatchers()->addInstanceId($configuration['uuid'], $configuration);
    return $configuration['uuid'];
  }

  /**
   * {@inheritdoc}
   */
  public function removeMatcher(MatcherInterface $matcher) {
    $this->getMatchers()->removeInstanceId($matcher->getUuid());
    $this->save();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setMatcherConfig($instance_id, array $configuration) {
    $this->matchers[$instance_id] = $configuration;
    $this->getMatchers()->setInstanceConfiguration($instance_id, $configuration);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return array(
      'attributes' => $this->getAttributes(),
      'matchers' => $this->getMatchers(),
    );
  }

  /**
   * Returns the attribute manager.
   *
   * @return \Drupal\Component\Plugin\PluginManagerInterface
   *   The attribute manager.
   */
  protected function getAttributeManager() {
    return \Drupal::service('plugin.manager.linkit.attribute');
  }

  /**
   * Returns the matcher manager.
   *
   * @return \Drupal\Component\Plugin\PluginManagerInterface
   *   The matcher manager.
   */
  protected function getMatcherManager() {
    return \Drupal::service('plugin.manager.linkit.matcher');
  }

}
