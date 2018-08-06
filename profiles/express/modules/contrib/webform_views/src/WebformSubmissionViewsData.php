<?php

namespace Drupal\webform_views;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionViewsData as WebformSubmissionViewsDataBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Views data for 'webform_submission' entity type.
 */
class WebformSubmissionViewsData extends WebformSubmissionViewsDataBase {

  /**
   * @var WebformElementManagerInterface
   */
  protected $webformElementManager;

  /**
   * @var EntityStorageInterface
   */
  protected $webformStorage;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('entity.manager'),
      $container->get('module_handler'),
      $container->get('string_translation'),
      $container->get('plugin.manager.webform.element'),
      $container->get('entity_type.manager')->getStorage('webform')
    );
  }

  /**
   * WebformSubmissionViewsData constructor.
   */
  public function __construct(EntityTypeInterface $entity_type, SqlEntityStorageInterface $storage_controller, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler, TranslationInterface $translation_manager, WebformElementManagerInterface $webform_element_manager, EntityStorageInterface $webform_storage) {
    parent::__construct($entity_type, $storage_controller, $entity_manager, $module_handler, $translation_manager);

    $this->webformElementManager = $webform_element_manager;
    $this->webformStorage = $webform_storage;
  }

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $base_table = $this->entityType->getBaseTable() ?: $this->entityType->id();

    // Reverse relationship on the "entity_type" and "entity_id" columns, i.e.
    // from an arbitrary entity to webform submissions that have been submitted
    // to it.
    foreach ($this->entityManager->getDefinitions() as $definition) {
      if ($definition instanceof ContentEntityTypeInterface) {
        $relationship = [
          'base' => $base_table,
          'field' => $definition->getKey('id'),
          'base field' => 'entity_id',
          'id' => 'standard',
          'extra' => [
            ['field' => 'entity_type', 'value' => $definition->id()],
          ],
        ];

        // Depending on whether the foreign entity has data table we join on its
        // data table or on its base table. Additionally, if it we join on the
        // data table, then we also must join on langcode column.
        if ($definition->getDataTable()) {
          $foreign_table = $definition->getDataTable();
          $relationship['extra'][] = ['field' => 'langcode', 'left_field' => 'langcode'];
        }
        else {
          $foreign_table = $definition->getBaseTable();
        }

        $data[$foreign_table]['webform_submission'] = [
          'title' => $this->t('Webform submissions'),
          'help' => $this->t('Webform submissions submitted to an entity.'),
        ];

        $data[$foreign_table]['webform_submission']['relationship'] = $relationship;
      }
    }

    foreach ($this->webformStorage->loadMultiple() as $webform) {
      foreach ($webform->getElementsInitializedAndFlattened() as $element) {
        $data = array_replace_recursive($data, $this->getWebformElementViewsData($element, $webform));
      }
    }

    return $data;
  }

  /**
   * Collect webform element views data.
   *
   * @param array $element
   *   Element whose views data is to be collected
   * @param \Drupal\webform\WebformInterface $webform
   *   Webform where $element belongs to
   *
   * @return array
   *   Views data that corresponds to the provided $element
   */
  protected function getWebformElementViewsData($element, WebformInterface $webform) {
    $data = [];

    $element_plugin = $this->webformElementManager->getElementInstance($element);
    if (isset($element_plugin->getPluginDefinition()['webform_views_handler'])) {
      $views_handler_class = $element_plugin->getPluginDefinition()['webform_views_handler'];
      $this->moduleHandler->alter('webform_views_element_views_handler', $views_handler_class, $element, $webform);
      if (is_subclass_of($views_handler_class, ContainerInjectionInterface::class)) {
        $views_handler = $views_handler_class::create(\Drupal::getContainer());
      }
      else {
        $views_handler = new $views_handler_class();
      }
      $data = $views_handler->getViewsData($element, $webform);
    }

    return $data;
  }

}
