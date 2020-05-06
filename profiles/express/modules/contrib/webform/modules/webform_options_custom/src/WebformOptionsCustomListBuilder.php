<?php

namespace Drupal\webform_options_custom;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;
use Drupal\webform\Utility\WebformDialogHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines a class to build a listing of webform options custom entities.
 *
 * @see \Drupal\webform_options_custom\Entity\WebformOptionsCustom
 */
class WebformOptionsCustomListBuilder extends ConfigEntityListBuilder {

  /**
   * Search keys.
   *
   * @var string
   */
  protected $keys;

  /**
   * Search category.
   *
   * @var string
   */
  protected $category;

  /**
   * Constructs a new WebformOptionsCustomListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, RequestStack $request_stack) {
    parent::__construct($entity_type, $storage);
    $this->request = $request_stack->getCurrentRequest();

    $this->keys = $this->request->query->get('search');
    $this->category = $this->request->query->get('category');
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    // Handler autocomplete redirect.
    if ($this->keys && preg_match('#\(([^)]+)\)$#', $this->keys, $match)) {
      if ($webform_options_custom = $this->getStorage()->load($match[1])) {
        return new RedirectResponse($webform_options_custom->toUrl()->setAbsolute(TRUE)->toString());
      }
    }

    $build = [];

    // Filter form.
    $build['filter_form'] = $this->buildFilterForm();

    // Display info.
    $build['info'] = $this->buildInfo();

    // Table.
    $build += parent::render();
    $build['table']['#sticky'] = TRUE;

    // Attachments.
    $build['#attached']['library'][] = 'webform/webform.tooltip';
    $build['#attached']['library'][] = 'webform/webform.admin.dialog';

    return $build;
  }

  /**
   * Build the filter form.
   *
   * @return array
   *   A render array representing the filter form.
   */
  protected function buildFilterForm() {
    $categories = $this->getStorage()->getCategories();
    return \Drupal::formBuilder()->getForm('\Drupal\webform_options_custom\Form\WebformOptionsCustomFilterForm', $this->keys, $this->category, $categories);
  }

  /**
   * Build information summary.
   *
   * @return array
   *   A render array representing the information summary.
   */
  protected function buildInfo() {
    $total = $this->getQuery($this->keys, $this->category)->count()->execute();
    if (!$total) {
      return [];
    }

    return [
      '#markup' => $this->formatPlural($total, '@total custom options', '@total custom options', ['@total' => $total]),
      '#prefix' => '<div>',
      '#suffix' => '</div>',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label / Description');
    $header['category'] = $this->t('Category');
    $header['element'] = $this->t('Element');
    $header['entity_reference'] = $this->t('Entity reference');
    $header['options'] = [
      'data' => $this->t('Options'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    $header['used_by'] = [
      'data' => $this->t('Used by'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\webform_options_custom\WebformOptionsCustomInterface $entity */
    $row['label'] = [
      'data' => [
        'link' => $entity->toLink($entity->label(), 'edit-form')->toRenderable(),
        'description' => ['#markup' => $entity->get('description'), '#prefix' => '<br/>'],
      ],
    ];
    $row['category'] = $entity->get('category');
    $row['element'] = ($entity->get('element')) ? $this->t('Yes') : $this->t('No');
    $row['entity_reference'] = ($entity->get('entity_reference')) ? $this->t('Yes') : $this->t('No');
    $row['options'] = $this->buildOptions($entity);
    $row['used_by'] = $this->buildUsedBy($entity);
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity, $type = 'edit') {
    $operations = parent::getDefaultOperations($entity);
    if ($entity->access('edit')) {
      $operations['preview'] = [
        'title' => $this->t('Preview'),
        'weight' => 23,
        'url' => Url::fromRoute('entity.webform_options_custom.preview_form', ['webform_options_custom' => $entity->id()]),
      ];
    }
    if ($entity->access('duplicate')) {
      $operations['duplicate'] = [
        'title' => $this->t('Duplicate'),
        'weight' => 24,
        'url' => Url::fromRoute('entity.webform_options_custom.duplicate_form', ['webform_options_custom' => $entity->id()]),
      ];
    }
    if (isset($operations['delete'])) {
      $operations['delete']['attributes'] = WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NARROW);
    }
    return $operations;
  }

  /**
   * Build options for a webform options custom entity.
   *
   * @param \Drupal\webform_options_custom\WebformOptionsCustomInterface $entity
   *   A webform options custom entity.
   *
   * @return string
   *   Options for a webform options custom entity.
   */
  protected function buildOptions(WebformOptionsCustomInterface $entity) {
    $options = $entity->getTemplateOptions();
    foreach ($options as $key => &$value) {
      if ($key != $value) {
        $value .= ' (' . $key . ')';
      }
    }
    return implode('; ', array_slice($options, 0, 12)) . (count($options) > 12 ? '; …' : '');
  }

  /**
   * Build list of webforms that the webform custom options is used by.
   *
   * @param \Drupal\webform_options_custom\WebformOptionsCustomInterface $webform_options_custom
   *   A webform options custom entity.
   *
   * @return array
   *   Table data containing list of webforms that the webform custom options is used by.
   */
  protected function buildUsedBy(WebformOptionsCustomInterface $webform_options_custom) {
    $links = [];
    $webforms = $this->getStorage()->getUsedByWebforms($webform_options_custom);
    foreach ($webforms as $id => $title) {
      $links[] = [
        '#type' => 'link',
        '#title' => $title,
        '#url' => Url::fromRoute('entity.webform.canonical', ['webform' => $id]),
        '#suffix' => '</br>',
      ];
    }
    return [
      'nowrap' => TRUE,
      'data' => $links,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildOperations(EntityInterface $entity) {
    return parent::buildOperations($entity) + [
      '#prefix' => '<div class="webform-dropbutton">',
      '#suffix' => '</div>',
    ];
  }

  /**
   * Get the base entity query filtered by search and category.
   *
   * @param string $keys
   *   (optional) Search key.
   * @param string $category
   *   (optional) Category.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   An entity query.
   */
  protected function getQuery($keys = '', $category = '') {
    $query = $this->getStorage()->getQuery();

    // Filter by key(word).
    if ($keys) {
      $or = $query->orConditionGroup()
        ->condition('id', $keys, 'CONTAINS')
        ->condition('title', $keys, 'CONTAINS')
        ->condition('description', $keys, 'CONTAINS')
        ->condition('help', $keys, 'CONTAINS')
        ->condition('template', $keys, 'CONTAINS')
        ->condition('url', $keys, 'CONTAINS')
        ->condition('options', $keys, 'CONTAINS');
      $query->condition($or);
    }

    // Filter by category.
    if ($category) {
      $query->condition('category', $category);
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $header = $this->buildHeader();
    $query = $this->getQuery($this->keys, $this->category);
    $query->tableSort($header);
    $query->pager($this->limit);
    return $query->execute();
  }

}
