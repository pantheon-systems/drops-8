<?php

namespace Drupal\webform_node\Controller;

use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;
use Drupal\webform\Utility\WebformDialogHelper;
use Drupal\webform\Utility\WebformElementHelper;
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
   * The webform entity reference manager.
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
   * @param \Drupal\webform\WebformSubmissionStorageInterface $webform_submission_storage
   *   The webform submission storage class.
   * @param \Drupal\webform\WebformEntityReferenceManagerInterface $webform_entity_reference_manager
   *   The webform entity reference manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatterInterface $date_formatter, ConfigEntityStorageInterface $node_type_storage, ConfigEntityStorageInterface $field_config_storage, WebformSubmissionStorageInterface $webform_submission_storage, WebformEntityReferenceManagerInterface $webform_entity_reference_manager) {
    parent::__construct($entity_type, $storage);

    $this->dateFormatter = $date_formatter;
    $this->nodeTypeStorage = $node_type_storage;
    $this->fieldConfigStorage = $field_config_storage;
    $this->submissionStorage = $webform_submission_storage;
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
      $container->get('entity_type.manager')->getDefinition('node'),
      $container->get('entity_type.manager')->getStorage('node'),
      $container->get('date.formatter'),
      $container->get('entity_type.manager')->getStorage('node_type'),
      $container->get('entity_type.manager')->getStorage('field_config'),
      $container->get('entity_type.manager')->getStorage('webform_submission'),
      $container->get('webform.entity_reference_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $webform = $this->webform;
    $header = [];
    $header['title'] = $this->t('Title');
    $header['type'] = [
      'data' => $this->t('Type'),
      'class' => [RESPONSIVE_PRIORITY_MEDIUM],
    ];
    if ($webform->hasVariants()) {
      $element_keys = $webform->getElementsVariant();
      foreach ($element_keys as $element_key) {
        $element = $webform->getElement($element_key);
        $header['element__' . $element_key] = [
          'data' => WebformElementHelper::getAdminTitle($element),
          'class' => [RESPONSIVE_PRIORITY_LOW],
        ];
      }
    }
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
    $header['results'] = [
      'data' => $this->t('Results'),
      'class' => [RESPONSIVE_PRIORITY_MEDIUM],
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $webform = $this->webform;

    /** @var \Drupal\node\NodeInterface $entity */
    $row['title']['data'] = [
      '#type' => 'link',
      '#title' => $entity->label(),
      '#url' => $entity->toUrl(),
    ];
    $row['type'] = node_get_type_label($entity);
    if ($webform->hasVariants()) {
      $variant_element_keys = $webform->getElementsVariant();
      foreach ($variant_element_keys as $variant_element_key) {
        $variants = [];
        foreach ($this->fieldNames as $field_name) {
          if (!$entity->hasField($field_name)) {
            continue;
          }
          $default_data = Yaml::decode($entity->$field_name->default_data);
          if (empty($default_data[$variant_element_key])) {
            continue;
          }
          $variant_instance_id = $default_data[$variant_element_key];
          if ($webform->getVariants()->has($variant_instance_id)) {
            $variant_plugin = $webform->getVariant($variant_instance_id);
            $variants[$default_data[$variant_element_key]] = $variant_plugin->label();
          }
        }
        $row['element__' . $variant_element_key] = [
          'data' => implode('; ', $variants),
          'class' => [RESPONSIVE_PRIORITY_LOW],
        ];
      }
    }
    $row['author']['data'] = [
      '#theme' => 'username',
      '#account' => $entity->getOwner(),
    ];
    $row['changed'] = $this->dateFormatter->format($entity->getChangedTime(), 'short');
    $row['node_status'] = $entity->isPublished() ? $this->t('Published') : $this->t('Not published');
    $row['webform_status'] = $this->getWebformStatus($entity);

    $result_total = $this->submissionStorage->getTotal($this->webform, $entity);
    $results_access = $entity->access('submission_view_any');
    $results_disabled = $this->webform->isResultsDisabled();
    if ($results_disabled || !$results_access) {
      $row['results'] = $result_total;
    }
    else {
      $route_parameters = [
        'node' => $entity->id(),
      ];
      $row['results'] = [
        'data' => [
          '#type' => 'link',
          '#title' => $result_total,
          '#attributes' => [
            'aria-label' => $this->formatPlural($result_total, '@count result for @label', '@count results for @label', ['@label' => $entity->label()]),
          ],
          '#url' => Url::fromRoute('entity.node.webform.results_submissions', $route_parameters),
        ],
      ];
    }

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
  public function buildOperations(EntityInterface $entity) {
    $build = [
      '#type' => 'operations',
      '#links' => $this->getOperations($entity),
      '#prefix' => '<div class="webform-dropbutton">',
      '#suffix' => '</div>',
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $route_parameters = [
      'node' => $entity->id(),
    ];
    $operations = [];
    if ($entity->access('update')) {
      $operations['edit'] = [
        'title' => $this->t('Edit'),
        'url' => $this->ensureDestination($entity->toUrl('edit-form')),
      ];
    }
    if ($entity->access('view')) {
      $operations['view'] = [
        'title' => $this->t('View'),
        'url' => $this->ensureDestination($entity->toUrl('canonical')),
      ];
    }
    if ($entity->access('submission_view_any') && !$this->webform->isResultsDisabled()) {
      $operations['results'] = [
        'title' => $this->t('Results'),
        'url' => Url::fromRoute('entity.node.webform.results_submissions', $route_parameters),
      ];
    }
    if ($entity->access('delete')) {
      $operations['delete'] = [
        'title' => $this->t('Delete'),
        'url' => $this->ensureDestination($entity->toUrl('delete-form')),
      ];
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = [];

    $build['info'] = $this->buildInfo();

    $build += parent::render();

    $build['table']['#sticky'] = TRUE;

    // Customize the empty message.
    $build['table']['#empty'] = $this->t('There are no webform node references.');

    // Must manually add local actions because we can't alter local actions and
    // add query string parameter.
    // @see https://www.drupal.org/node/2585169
    $local_actions = [];

    if ($this->webform->hasVariants()) {
      foreach ($this->nodeTypes as $bundle => $node_type) {
        if ($node_type->access('create')) {
          $local_actions['webform_node.references.add_form'] = [
            '#theme' => 'menu_local_action',
            '#link' => [
              'title' => $this->t('Add reference'),
              'url' => Url::fromRoute('entity.webform.references.add_form', ['webform' => $this->webform->id()]),
              'attributes' => WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NARROW),
            ],
          ];
          WebformDialogHelper::attachLibraries($local_actions['webform_node.references.add_form']);
        }
      }
    }
    else {
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

  /**
   * Build information summary.
   *
   * @return array
   *   A render array representing the information summary.
   */
  protected function buildInfo() {
    $total = $this->getTotal();
    return [
      '#markup' => $this->formatPlural($total, '@count reference', '@count references'),
      '#prefix' => '<div>',
      '#suffix' => '</div>',
    ];
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
   * Get the total number of references.
   *
   * @return int
   *   The total number of references.
   */
  protected function getTotal() {
    $query = $this->getStorage()->getQuery()
      ->sort($this->entityType->getKey('id'));

    // Add field names.
    $or = $query->orConditionGroup();
    foreach ($this->fieldNames as $field_name) {
      $or->condition($field_name . '.target_id', $this->webform->id());
    }
    $query->condition($or);

    return count($query->execute());
  }

}
