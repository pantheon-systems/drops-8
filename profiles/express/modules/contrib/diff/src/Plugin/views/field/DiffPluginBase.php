<?php

namespace Drupal\diff\Plugin\views\field;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\field\UncacheableFieldHandlerTrait;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for diff view field plugins.
 */
class DiffPluginBase extends FieldPluginBase {

  use UncacheableFieldHandlerTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a DiffPluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $row, $field = NULL) {
    return '<!--form-item-' . $this->options['id'] . '--' . $row->index . '-->';
  }

  /**
   * {@inheritdoc}
   */
  public function viewsForm(array &$form, FormStateInterface $form_state) {
    if (!empty($this->view->result)) {
      $form[$this->options['id']]['#tree'] = TRUE;
      foreach ($this->view->result as $row_index => $row) {
        $entity = $row->_entity;
        $form[$this->options['id']][$row_index] = [
          '#type' => 'radio',
          '#parents' => [$this->options['id']],
          '#title' => $this->t('Compare this item'),
          '#title_display' => 'invisible',
          '#return_value' => $this->calculateEntityDiffFormKey($entity),
        ];
      }
    }
  }

  /**
   * Calculates a diff form key.
   *
   * This generates a key that is used as the checkbox return value when
   * submitting a diff form. This key allows the entity for the row to be loaded
   * totally independently of the executed view row.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to calculate a diff form key for.
   *
   * @return string
   *   The diff form key representing the entity's id, language and revision (if
   *   applicable) as one string.
   *
   * @see self::loadEntityFromDiffFormKey()
   */
  protected function calculateEntityDiffFormKey(EntityInterface $entity) {
    $key_parts = [$entity->language()->getId(), $entity->id()];

    if ($entity instanceof RevisionableInterface) {
      $key_parts[] = $entity->getRevisionId();
    }

    // An entity ID could be an arbitrary string (although they are typically
    // numeric). JSON then Base64 encoding ensures the diff_form_key is
    // safe to use in HTML, and that the key parts can be retrieved.
    $key = Json::encode($key_parts);
    return base64_encode($key);
  }

  /**
   * Loads an entity based on a diff form key.
   *
   * @param string $diff_form_key
   *   The diff form key representing the entity's ID, language and revision (if
   *   applicable) as one string.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity loaded in the state (language, optionally revision) specified
   *   as part of the diff form key.
   *
   * @throws \UnexpectedValueException
   *   If no entity can be found for the given diff form key.
   */
  protected function loadEntityFromDiffFormKey($diff_form_key) {
    $key = base64_decode($diff_form_key);
    $key_parts = Json::decode($key);
    $revision_id = NULL;

    // If there are 3 items, vid will be last.
    if (count($key_parts) === 3) {
      $revision_id = array_pop($key_parts);
    }

    // The first two items will always be langcode and ID.
    $id = array_pop($key_parts);
    $langcode = array_pop($key_parts);

    // Load the entity or a specific revision depending on the given key.
    $storage = $this->entityTypeManager->getStorage($this->getEntityType());
    $entity = $revision_id ? $storage->loadRevision($revision_id) : $storage->load($id);

    if (empty($entity)) {
      throw new \UnexpectedValueException('Entity not found: ' . $diff_form_key);
    }

    if ($entity instanceof TranslatableInterface) {
      $entity = $entity->getTranslation($langcode);
    }

    return $entity;
  }

}
