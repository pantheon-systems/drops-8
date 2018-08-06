<?php

namespace Drupal\webform_node\Controller;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;
use Drupal\webform\WebformEntityReferenceManagerInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a controller for webform node references.
 *
 * Even though this is controller we are extending EntityListBuilder because
 * the it's interface and patterns are application for display webform node
 * references.
 */
class WebformNodeReferencesListController extends EntityListBuilder implements ContainerInjectionInterface {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Webform submission storage.
   *
   * @var \Drupal\webform\WebformSubmissionStorageInterface
   */
  protected $submissionStorage;

  /**
   * Node type storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $nodeTypeStorage;

  /**
   * Field config storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $fieldConfigStorage;

  /**
   * The webform entity reference manager
   *
   * @var \Drupal\webform\WebformEntityReferenceManagerInterface
   */
  protected $webformEntityReferenceManager;

  /**
   * The webform.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;

  /**
   * Webform node field names.
   *
   * @var array
   */
  protected $fieldNames;

  /**
   * Webform node type.
   *
   * @var array
   */
  protected $nodeTypes;

  /**
   * Provides the listing page for webform node references.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function listing(WebformInterface $webform) {
    $this->webform = $webform;
    if (empty($this->fieldNames)) {
      return [
        '#type' => 'webform_message',
        '#message_type' => 'warning',
        '#message_message' => $this->t('There are no nodes with webform entity references. Please create add a Webform field to content type.'),
      ];
    }
    else {
      return $this->render();
    }
  }

  /**
   * Constructs a new WebformNodeReferencesListController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $node_type_storage
   *   The node type storage class.
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $field_config_storage
   *   The field config storage class.
   * @param \Drupal\webform\WebformSubmissionStorageInterface $webform_submsision_storage
   *   The webform submission storage class.
   * @param \Drupal\webform\WebformEntityReferenceManagerInterface $webform_entity_reference_manager
   *   The webform entity reference manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatterInterface $date_formatter, ConfigEntityStorageInterface $node_type_storage, ConfigEntityStorageInterface $field_config_storage, WebformSubmissionStorageInterface $webform_submsision_storage, WebformEntityReferenceManagerInterface $webform_entity_reference_manager) {
    parent::__construct($entity_type, $storage);

    $this->dateFormatter = $date_formatter;
    $this->nodeTypeStorage = $node_type_storage;
    $this->fieldConfigStorage = $field_config_storage;
    $this->submissionStorage = $webform_submsision_storage;
    $this->webformEntityReferenceManager = $webform_entity_reference_manager;

    $this->nodeTypes = [];
    $this->fieldNames = [];

    /** @var \Drupal\node\Entity\NodeType[] $node_types */
    $node_types = $this->nodeTypeStorage->loadMultiple();
    /** @var \Drupal\field\FieldConfigInterface[] $field_configs */
    $field_configs = $this->fieldConfigStorage->loadByProperties(['entity_type' => 'node']);
    foreach ($field_configs as $field_config) {
      if ($field_config->get('field_type') === 'webform') {
        $bundle = $field_config->get('bundle');
        $this->nodeTypes[$bundle] = $node_types[$bundle];

        $field_name = $field_config->get('field_name');
        $this->fieldNames[$field_name] = $field_name;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getDefinition('node'),
      $container->get('entity.manager')->getStorage('node'),
      $container->get('date.formatter'),
      $container->get('entity.manager')->getStorage('node_type'),
      $container->get('entity.manager')->getStorage('field_config'),
      $container->get('entity.manager')->getStorage('webform_submission'),
      $container->get('webform.entity_reference_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->sort($this->entityType->getKey('id'));

    // Add field names.
    $or = $query->orConditionGroup();
    foreach ($this->fieldNames as $field_name) {
      $or->condition($field_name . '.target_id', $this->webform->id());
    }
    $query->condition($or);

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }

    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [];
    $header['title'] = $this->t('Title');
    $header['type'] = [
      'data' => $this->t('Type'),
      'class' => [RESPONSIVE_PRIORITY_MEDIUM],
    ];
    $header['author'] = [
      'data' => $this->t('Author'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    $header['changed'] = [
      'data' => $this->t('Updated'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    $header['node_status'] = [
      'data' => $this->t('Node status'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    $header['webform_status'] = [
      'data' => $this->t('Webform status'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    $header['results_total'] = [
      'data' => $this->t('Total Results'),
      'class' => [RESPONSIVE_PRIORITY_MEDIUM],
    ];
    $header['results_operations'] = [
      'data' => $this->t('Operations'),
      'class' => [RESPONSIVE_PRIORITY_MEDIUM],
    ];
    $header['operations'] = [
      'data' => '',
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\node\NodeInterface $entity */
    $row['title']['data'] = [
      '#type' => 'link',
      '#title' => $entity->label(),
      '#url' => $entity->toUrl(),
    ];
    $row['type'] = node_get_type_label($entity);
    $row['author']['data'] = [
      '#theme' => 'username',
      '#account' => $entity->getOwner(),
    ];
    $row['changed'] = $this->dateFormatter->format($entity->getChangedTime(), 'short');
    $row['node_status'] = $entity->isPublished() ? $this->t('Published') : $this->t('Not published');
    $row['webform_status'] = $this->getWebformStatus($entity);
    $row['results_total'] = $this->submissionStorage->getTotal($this->webform, $entity);
    $row['results_operations']['data'] = [
      '#type' => 'operations',
      '#links' => $this->getDefaultOperations($entity, 'results'),
      '#prefix' => '<div class="webform-dropbutton">',
      '#suffix' => '</div>',
    ];
    $row['operations']['data'] = $this->buildOperations($entity);
    return $row + parent::buildRow($entity);
  }

  /**
   * Get the webform node's status.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The node.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   The webform node status.
   *
   * @see \Drupal\webform\Plugin\Field\FieldFormatter\WebformEntityReferenceFormatterBase::isOpen
   */
  protected function getWebformStatus(EntityInterface $entity) {
    // Get source entity's webform field.
    $webform_field_name = $this->webformEntityReferenceManager->getFieldName($entity);
    if (!$webform_field_name) {
      return NULL;
    }

    if ($entity->$webform_field_name->target_id != $this->webform->id()) {
      return NULL;
    }

    $webform_field = $entity->$webform_field_name;
    if ($webform_field->status == WebformInterface::STATUS_OPEN) {
      return $this->t('Open');
    }

    if ($webform_field->status == WebformInterface::STATUS_SCHEDULED) {
      $is_opened = TRUE;
      if ($webform_field->open && strtotime($webform_field->open) > time()) {
        $is_opened = FALSE;
      }

      $is_closed = FALSE;
      if ($webform_field->close && strtotime($webform_field->close) < time()) {
        $is_closed = TRUE;
      }
      return ($is_opened && !$is_closed) ? $this->t('Open') : $this->t('Closed');
    }

    return $this->t('Closed');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity, $type = 'edit') {
    $route_parameters = [
      'node' => $entity->id(),
    ];
    if ($type == 'results') {
      $operations = [];
      if ($entity->access('submission_view_any')) {
        $operations['submissions'] = [
          'title' => $this->t('Submissions'),
          'url' => Url::fromRoute('entity.node.webform.results_submissions', $route_parameters),
        ];
        $operations['export'] = [
          'title' => $this->t('Download'),
          'url' => Url::fromRoute('entity.node.webform.results_export', $route_parameters),
        ];
      }
      if ($entity->access('submission_delete_any')) {
        $operations['clear'] = [
          'title' => $this->t('Clear'),
          'url' => Url::fromRoute('entity.node.webform.results_clear', $route_parameters),
        ];
      }
    }
    else {
      $operations = parent::getDefaultOperations($entity);
      if ($entity->access('submission_update_any')) {
        $operations['test'] = [
          'title' => $this->t('Test'),
          'weight' => 21,
          'url' => Url::fromRoute('entity.node.webform.test_form', $route_parameters),
        ];
      }
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();

    // Customize the empty message.
    $build['table']['#empty'] = $this->t('There are no webform node references.');

    // Must manually add local actions because we can't alter local actions and
    // add query string parameter.
    // @see https://www.drupal.org/node/2585169
    $local_actions = [];
    foreach ($this->nodeTypes as $bundle => $node_type) {
      if ($node_type->access('create')) {
        $local_actions['webform_node.references.add_' . $bundle] = [
          '#theme' => 'menu_local_action',
          '#link' => [
            'title' => $this->t('Add @title', ['@title' => $node_type->label()]),
            'url' => Url::fromRoute('node.add', ['node_type' => $bundle], ['query' => ['webform_id' => $this->webform->id()]]),
          ],
        ];
      }
    }
    if ($local_actions) {
      $build['local_actions'] = [
        '#prefix' => '<ul class="action-links">',
        '#suffix' => '</ul>',
        '#weight' => -100,
      ] + $local_actions;
    }

    $build['#attached']['library'][] = 'webform_node/webform_node.references';

    return $build;
  }

}
