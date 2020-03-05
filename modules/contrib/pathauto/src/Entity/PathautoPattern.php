<?php

namespace Drupal\pathauto\Entity;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Condition\ConditionPluginCollection;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;
use Drupal\pathauto\PathautoPatternInterface;

/**
 * Defines the Pathauto pattern entity.
 *
 * @ConfigEntityType(
 *   id = "pathauto_pattern",
 *   label = @Translation("Pathauto pattern"),
 *   handlers = {
 *     "list_builder" = "Drupal\pathauto\PathautoPatternListBuilder",
 *     "form" = {
 *       "default" = "Drupal\pathauto\Form\PatternEditForm",
 *       "duplicate" = "Drupal\pathauto\Form\PatternDuplicateForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *       "enable" = "Drupal\pathauto\Form\PatternEnableForm",
 *       "disable" = "Drupal\pathauto\Form\PatternDisableForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "pattern",
 *   admin_permission = "administer pathauto",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "weight" = "weight",
 *     "status" = "status"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "type",
 *     "pattern",
 *     "selection_criteria",
 *     "selection_logic",
 *     "weight",
 *     "relationships"
 *   },
 *   lookup_keys = {
 *     "type",
 *     "status",
 *   },
 *   links = {
 *     "collection" = "/admin/config/search/path/patterns",
 *     "edit-form" = "/admin/config/search/path/patterns/{pathauto_pattern}",
 *     "delete-form" = "/admin/config/search/path/patterns/{pathauto_pattern}/delete",
 *     "enable" = "/admin/config/search/path/patterns/{pathauto_pattern}/enable",
 *     "disable" = "/admin/config/search/path/patterns/{pathauto_pattern}/disable",
 *     "duplicate-form" = "/admin/config/search/path/patterns/{pathauto_pattern}/duplicate"
 *   }
 * )
 */
class PathautoPattern extends ConfigEntityBase implements PathautoPatternInterface {

  /**
   * The Pathauto pattern ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Pathauto pattern label.
   *
   * @var string
   */
  protected $label;

  /**
   * The pattern type.
   *
   * A string denoting the type of pathauto pattern this is. For a node path
   * this would be 'node', for users it would be 'user', and so on. This allows
   * for arbitrary non-entity patterns to be possible if applicable.
   *
   * @var string
   */
  protected $type;

  /**
   * @var \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection
   */
  protected $aliasTypeCollection;

  /**
   * A tokenized string for alias generation.
   *
   * @var string
   */
  protected $pattern;

  /**
   * The plugin configuration for the selection criteria condition plugins.
   *
   * @var array
   */
  protected $selection_criteria = [];

  /**
   * The selection logic for this pattern entity (either 'and' or 'or').
   *
   * @var string
   */
  protected $selection_logic = 'and';

  /**
   * @var int
   */
  protected $weight = 0;

  /**
   * @var array[]
   *   Keys are context tokens, and values are arrays with the following keys:
   *   - label (string|null, optional): The human-readable label of this
   *     relationship.
   */
  protected $relationships = [];

  /**
   * The plugin collection that holds the selection criteria condition plugins.
   *
   * @var \Drupal\Component\Plugin\LazyPluginCollection
   */
  protected $selectionConditionCollection;

  /**
   * {@inheritdoc}
   *
   * Not using core's default logic around ConditionPluginCollection since it
   * incorrectly assumes no condition will ever be applied twice.
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    $criteria = [];
    foreach ($this->getSelectionConditions() as $id => $condition) {
      $criteria[$id] = $condition->getConfiguration();
    }
    $this->selection_criteria = $criteria;

    // Invalidate the static caches.
    \Drupal::service('pathauto.generator')->resetCaches();
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    // Invalidate the static caches.
    \Drupal::service('pathauto.generator')->resetCaches();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    $this->calculatePluginDependencies($this->getAliasType());

    foreach ($this->getSelectionConditions() as $instance) {
      $this->calculatePluginDependencies($instance);
    }

    return $this->getDependencies();
  }

  /**
   * {@inheritdoc}
   */
  public function getPattern() {
    return $this->pattern;
  }

  /**
   * {@inheritdoc}
   */
  public function setPattern($pattern) {
    $this->pattern = $pattern;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function getAliasType() {
    if (!$this->aliasTypeCollection) {
      $this->aliasTypeCollection = new DefaultSingleLazyPluginCollection(\Drupal::service('plugin.manager.alias_type'), $this->getType(), ['default' => $this->getPattern()]);
    }
    return $this->aliasTypeCollection->get($this->getType());
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->weight = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getContexts() {
    $contexts = $this->getAliasType()->getContexts();
    foreach ($this->getRelationships() as $token => $definition) {
      /** @var \Drupal\ctools\TypedDataResolver $resolver */
      $resolver = \Drupal::service('ctools.typed_data.resolver');
      $context = $resolver->convertTokenToContext($token, $contexts);
      $context_definition = $context->getContextDefinition();
      if (!empty($definition['label'])) {
        $context_definition->setLabel($definition['label']);
      }
      $contexts[$token] = $context;
    }
    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function hasRelationship($token) {
    return isset($this->relationships[$token]);
  }

  /**
   * {@inheritdoc}
   */
  public function addRelationship($token, $label = NULL) {
    if (!$this->hasRelationship($token)) {
      $this->relationships[$token] = [
        'label' => $label,
      ];
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function replaceRelationship($token, $label) {
    if ($this->hasRelationship($token)) {
      $this->relationships[$token] = [
        'label' => $label,
      ];
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeRelationship($token) {
    unset($this->relationships[$token]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRelationships() {
    return $this->relationships;
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectionConditions() {
    if (!$this->selectionConditionCollection) {
      $this->selectionConditionCollection = new ConditionPluginCollection(\Drupal::service('plugin.manager.condition'), $this->get('selection_criteria'));
    }
    return $this->selectionConditionCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function addSelectionCondition(array $configuration) {
    $configuration['uuid'] = $this->uuidGenerator()->generate();
    $this->getSelectionConditions()->addInstanceId($configuration['uuid'], $configuration);
    return $configuration['uuid'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectionCondition($condition_id) {
    return $this->getSelectionConditions()->get($condition_id);
  }

  /**
   * {@inheritdoc}
   */
  public function removeSelectionCondition($condition_id) {
    $this->getSelectionConditions()->removeInstanceId($condition_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectionLogic() {
    return $this->selection_logic;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($object) {
    if ($this->getAliasType()->applies($object)) {
      $definitions = $this->getAliasType()->getContextDefinitions();
      if (count($definitions) > 1) {
        throw new \Exception("Alias types do not support more than one context.");
      }
      $keys = array_keys($definitions);
      // Set the context object on our Alias plugin before retrieving contexts.
      $this->getAliasType()->setContextValue($keys[0], $object);
      /** @var \Drupal\Core\Plugin\Context\ContextInterface[] $base_contexts */
      $contexts = $this->getContexts();
      /** @var \Drupal\Core\Plugin\Context\ContextHandler $context_handler */
      $context_handler = \Drupal::service('context.handler');
      $conditions = $this->getSelectionConditions();
      foreach ($conditions as $condition) {

        // As the context object is kept and only the value is switched out,
        // it can over time grow to a huge number of cache contexts. Reset it
        // if there are 100 cache tags to prevent cache tag merging getting too
        // slow.
        foreach ($condition->getContextDefinitions() as $name => $context_definition) {
          if (count($condition->getContext($name)->getCacheTags()) > 100) {
            $condition->setContext($name, new Context($context_definition));
          }
        }

        if ($condition instanceof ContextAwarePluginInterface) {
          try {
            $context_handler->applyContextMapping($condition, $contexts);
          }
          catch (ContextException $e) {
            watchdog_exception('pathauto', $e);
            return FALSE;
          }
        }
        $result = $condition->execute();
        if ($this->getSelectionLogic() == 'and' && !$result) {
          return FALSE;
        }
        elseif ($this->getSelectionLogic() == 'or' && $result) {
          return TRUE;
        }
      }
      return TRUE;
    }
    return FALSE;
  }

}
