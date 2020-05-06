<?php

namespace Drupal\webform_access;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\webform\Utility\WebformDialogHelper;
use Drupal\webform_access\Entity\WebformAccessGroup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of webform access type entities.
 *
 * @see \Drupal\webform\Entity\WebformOption
 */
class WebformAccessTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $limit = FALSE;

  /**
   * Access group storage.
   *
   * @var \Drupal\webform_access\WebformAccessGroupStorageInterface
   */
  protected $accessGroupStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, ConfigEntityStorageInterface $access_group_storage) {
    parent::__construct($entity_type, $storage);
    $this->accessGroupStorage = $access_group_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('entity.manager')->getStorage('webform_access_group')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = [];

    // Display info.
    $build['info'] = $this->buildInfo();

    // Table.
    $build += parent::render();
    $build['table']['#sticky'] = TRUE;

    // Attachments.
    $build['#attached']['library'][] = 'webform/webform.admin.dialog';

    return $build;
  }

  /**
   * Build information summary.
   *
   * @return array
   *   A render array representing the information summary.
   */
  protected function buildInfo() {
    $total = $this->getStorage()->getQuery()->count()->execute();
    if (!$total) {
      return [];
    }

    return [
      '#markup' => $this->formatPlural($total, '@total access type', '@total access types', ['@total' => $total]),
      '#prefix' => '<div>',
      '#suffix' => '</div>',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['groups'] = [
      'data' => $this->t('Groups'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\webform_access\WebformAccessTypeInterface $entity */

    // Label.
    $row['label'] = $entity->toLink($entity->label(), 'edit-form');

    // Groups.
    $entity_ids = $this->accessGroupStorage->getQuery()
      ->condition('type', $entity->id())
      ->execute();
    $items = [];
    if ($entity_ids) {
      $webform_access_groups = WebformAccessGroup::loadMultiple($entity_ids);
      foreach ($webform_access_groups as $webform_access_group) {
        $items[] = $webform_access_group->label();
      }
    }
    $row['groups'] = ['data' => ['#theme' => 'item_list', '#items' => $items]];
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity, $type = 'edit') {
    $operations = parent::getDefaultOperations($entity);
    if (isset($operations['delete'])) {
      $operations['delete']['attributes'] = WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NARROW);
    }
    return $operations;
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

}
