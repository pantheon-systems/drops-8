<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityDisplayBase.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Entity\Display\EntityDisplayInterface;

/**
 * Provides a common base class for entity view and form displays.
 */
abstract class EntityDisplayBase extends ConfigEntityBase implements EntityDisplayInterface {

  /**
   * The 'mode' for runtime EntityDisplay objects used to render entities with
   * arbitrary display options rather than a configured view mode or form mode.
   *
   * @todo Prevent creation of a mode with this ID
   *   https://www.drupal.org/node/2410727
   */
  const CUSTOM_MODE = '_custom';

  /**
   * Unique ID for the config entity.
   *
   * @var string
   */
  protected $id;

  /**
   * Entity type to be displayed.
   *
   * @var string
   */
  protected $targetEntityType;

  /**
   * Bundle to be displayed.
   *
   * @var string
   */
  protected $bundle;

  /**
   * A list of field definitions eligible for configuration in this display.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface[]
   */
  protected $fieldDefinitions;

  /**
   * View or form mode to be displayed.
   *
   * @var string
   */
  protected $mode = self::CUSTOM_MODE;

  /**
   * Whether this display is enabled or not. If the entity (form) display
   * is disabled, we'll fall back to the 'default' display.
   *
   * @var bool
   */
  protected $status;

  /**
   * List of component display options, keyed by component name.
   *
   * @var array
   */
  protected $content = array();

  /**
   * List of components that are set to be hidden.
   *
   * @var array
   */
  protected $hidden = array();

  /**
   * The original view or form mode that was requested (case of view/form modes
   * being configured to fall back to the 'default' display).
   *
   * @var string
   */
  protected $originalMode;

  /**
   * The plugin objects used for this display, keyed by field name.
   *
   * @var array
   */
  protected $plugins = array();

  /**
   * Context in which this entity will be used (e.g. 'display', 'form').
   *
   * @var string
   */
  protected $displayContext;

  /**
   * The plugin manager used by this entity type.
   *
   * @var \Drupal\Component\Plugin\PluginManagerBase
   */
  protected $pluginManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    if (!isset($values['targetEntityType']) || !isset($values['bundle'])) {
      throw new \InvalidArgumentException('Missing required properties for an EntityDisplay entity.');
    }

    if (!$this->entityManager()->getDefinition($values['targetEntityType'])->isSubclassOf('\Drupal\Core\Entity\FieldableEntityInterface')) {
      throw new \InvalidArgumentException('EntityDisplay entities can only handle fieldable entity types.');
    }

    $this->renderer = \Drupal::service('renderer');

    // A plugin manager and a context type needs to be set by extending classes.
    if (!isset($this->pluginManager)) {
      throw new \RuntimeException('Missing plugin manager.');
    }
    if (!isset($this->displayContext)) {
      throw new \RuntimeException('Missing display context type.');
    }

    parent::__construct($values, $entity_type);

    $this->originalMode = $this->mode;

    $this->init();
  }

  /**
   * Initializes the display.
   *
   * This fills in default options for components:
   * - that are not explicitly known as either "visible" or "hidden" in the
   *   display,
   * - or that are not supposed to be configurable.
   */
  protected function init() {
    // Only populate defaults for "official" view modes and form modes.
    if ($this->mode !== static::CUSTOM_MODE) {
      // Fill in defaults for extra fields.
      $context = $this->displayContext == 'view' ? 'display' : $this->displayContext;
      $extra_fields = \Drupal::entityManager()->getExtraFields($this->targetEntityType, $this->bundle);
      $extra_fields = isset($extra_fields[$context]) ? $extra_fields[$context] : array();
      foreach ($extra_fields as $name => $definition) {
        if (!isset($this->content[$name]) && !isset($this->hidden[$name])) {
          // Extra fields are visible by default unless they explicitly say so.
          if (!isset($definition['visible']) || $definition['visible'] == TRUE) {
            $this->content[$name] = array(
              'weight' => $definition['weight']
            );
          }
          else {
            $this->hidden[$name] = TRUE;
          }
        }
      }

      // Fill in defaults for fields.
      $fields = $this->getFieldDefinitions();
      foreach ($fields as $name => $definition) {
        if (!$definition->isDisplayConfigurable($this->displayContext) || (!isset($this->content[$name]) && !isset($this->hidden[$name]))) {
          $options = $definition->getDisplayOptions($this->displayContext);

          if (!empty($options['type']) && $options['type'] == 'hidden') {
            $this->hidden[$name] = TRUE;
          }
          elseif ($options) {
            $this->content[$name] = $this->pluginManager->prepareConfiguration($definition->getType(), $options);
          }
          // Note: (base) fields that do not specify display options are not
          // tracked in the display at all, in order to avoid cluttering the
          // configuration that gets saved back.
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityTypeId() {
    return $this->targetEntityType;
  }

  /**
   * {@inheritdoc}
   */
  public function getMode() {
    return $this->get('mode');
  }

  /**
   * {@inheritdoc}
   */
  public function getOriginalMode() {
    return $this->get('originalMode');
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetBundle() {
    return $this->bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function setTargetBundle($bundle) {
    $this->set('bundle', $bundle);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->targetEntityType . '.' . $this->bundle . '.' . $this->mode;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage, $update = TRUE) {
    ksort($this->content);
    ksort($this->hidden);
    parent::preSave($storage, $update);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    $target_entity_type = $this->entityManager()->getDefinition($this->targetEntityType);

    $bundle_entity_type_id = $target_entity_type->getBundleEntityType();
    if ($bundle_entity_type_id != 'bundle') {
      // If the target entity type uses entities to manage its bundles then
      // depend on the bundle entity.
      if (!$bundle_entity = $this->entityManager()->getStorage($bundle_entity_type_id)->load($this->bundle)) {
        throw new \LogicException("Missing bundle entity, entity type $bundle_entity_type_id, entity id {$this->bundle}.");
      }
      $this->addDependency('config', $bundle_entity->getConfigDependencyName());
    }
    else {
      // Depend on the provider of the entity type.
      $this->addDependency('module', $target_entity_type->getProvider());
    }

    // If field.module is enabled, add dependencies on 'field_config' entities
    // for both displayed and hidden fields. We intentionally leave out base
    // field overrides, since the field still exists without them.
    if (\Drupal::moduleHandler()->moduleExists('field')) {
      $components = $this->content + $this->hidden;
      $field_definitions = $this->entityManager()->getFieldDefinitions($this->targetEntityType, $this->bundle);
      foreach (array_intersect_key($field_definitions, $components) as $field_name => $field_definition) {
        if ($field_definition instanceof ConfigEntityInterface && $field_definition->getEntityTypeId() == 'field_config') {
          $this->addDependency('config', $field_definition->getConfigDependencyName());
        }
      }
    }

    // Depend on configured modes.
    if ($this->mode != 'default') {
      $mode_entity = $this->entityManager()->getStorage('entity_' . $this->displayContext . '_mode')->load($target_entity_type->id() . '.' . $this->mode);
      $this->addDependency('config', $mode_entity->getConfigDependencyName());
    }
    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    $properties = parent::toArray();
    // Do not store options for fields whose display is not set to be
    // configurable.
    foreach ($this->getFieldDefinitions() as $field_name => $definition) {
      if (!$definition->isDisplayConfigurable($this->displayContext)) {
        unset($properties['content'][$field_name]);
        unset($properties['hidden'][$field_name]);
      }
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function createCopy($mode) {
    $display = $this->createDuplicate();
    $display->mode = $display->originalMode = $mode;
    return $display;
  }

  /**
   * {@inheritdoc}
   */
  public function getComponents() {
    return $this->content;
  }

  /**
   * {@inheritdoc}
   */
  public function getComponent($name) {
    return isset($this->content[$name]) ? $this->content[$name] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setComponent($name, array $options = array()) {
    // If no weight specified, make sure the field sinks at the bottom.
    if (!isset($options['weight'])) {
      $max = $this->getHighestWeight();
      $options['weight'] = isset($max) ? $max + 1 : 0;
    }

    // For a field, fill in default options.
    if ($field_definition = $this->getFieldDefinition($name)) {
      $options = $this->pluginManager->prepareConfiguration($field_definition->getType(), $options);
    }

    // Ensure we always have an empty settings and array.
    $options += ['settings' => [], 'third_party_settings' => []];

    $this->content[$name] = $options;
    unset($this->hidden[$name]);
    unset($this->plugins[$name]);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeComponent($name) {
    $this->hidden[$name] = TRUE;
    unset($this->content[$name]);
    unset($this->plugins[$name]);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHighestWeight() {
    $weights = array();

    // Collect weights for the components in the display.
    foreach ($this->content as $options) {
      if (isset($options['weight'])) {
        $weights[] = $options['weight'];
      }
    }

    // Let other modules feedback about their own additions.
    $weights = array_merge($weights, \Drupal::moduleHandler()->invokeAll('field_info_max_weight', array($this->targetEntityType, $this->bundle, $this->displayContext, $this->mode)));

    return $weights ? max($weights) : NULL;
  }

  /**
   * Gets the field definition of a field.
   */
  protected function getFieldDefinition($field_name) {
    $definitions = $this->getFieldDefinitions();
    return isset($definitions[$field_name]) ? $definitions[$field_name] : NULL;
  }

  /**
   * Gets the definitions of the fields that are candidate for display.
   */
  protected function getFieldDefinitions() {
    if (!isset($this->fieldDefinitions)) {
      $definitions = \Drupal::entityManager()->getFieldDefinitions($this->targetEntityType, $this->bundle);
      // For "official" view modes and form modes, ignore fields whose
      // definition states they should not be displayed.
      if ($this->mode !== static::CUSTOM_MODE) {
        $definitions = array_filter($definitions, array($this, 'fieldHasDisplayOptions'));
      }
      $this->fieldDefinitions = $definitions;
    }

    return $this->fieldDefinitions;
  }

  /**
   * Determines if a field has options for a given display.
   *
   * @param FieldDefinitionInterface $definition
   *   A field definition.
   * @return array|null
   */
  private function fieldHasDisplayOptions(FieldDefinitionInterface $definition) {
    // The display only cares about fields that specify display options.
    // Discard base fields that are not rendered through formatters / widgets.
    return $definition->getDisplayOptions($this->displayContext);
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = parent::onDependencyRemoval($dependencies);
    foreach ($dependencies['config'] as $entity) {
      if ($entity->getEntityTypeId() == 'field_config') {
        // Remove components for fields that are being deleted.
        $this->removeComponent($entity->getName());
        unset($this->hidden[$entity->getName()]);
        $changed = TRUE;
      }
    }
    foreach ($this->getComponents() as $name => $component) {
      if (isset($component['type']) && $definition = $this->pluginManager->getDefinition($component['type'], FALSE)) {
        if (in_array($definition['provider'], $dependencies['module'])) {
          // Revert to the defaults if the plugin that supplies the widget or
          // formatter depends on a module that is being uninstalled.
          $this->setComponent($name);
          $changed = TRUE;
        }
      }
    }
    return $changed;
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    // Only store the definition, not external objects or derived data.
    $keys = array_keys($this->toArray());
    // In addition, we need to keep the entity type and the "is new" status.
    $keys[] = 'entityTypeId';
    $keys[] = 'enforceIsNew';
    // Keep track of the serialized keys, to avoid calling toArray() again in
    // __wakeup(). Because of the way __sleep() works, the data has to be
    // present in the object to be included in the serialized values.
    $keys[] = '_serializedKeys';
    $this->_serializedKeys = $keys;
    return $keys;
  }

  /**
   * {@inheritdoc}
   */
  public function __wakeup() {
    // Determine what were the properties from toArray() that were saved in
    // __sleep().
    $keys = $this->_serializedKeys;
    unset($this->_serializedKeys);
    $values = array_intersect_key(get_object_vars($this), array_flip($keys));
    // Run those values through the __construct(), as if they came from a
    // regular entity load.
    $this->__construct($values, $this->entityTypeId);
  }

}
