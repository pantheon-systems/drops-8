<?php

namespace Drupal\webform\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides route responses for Webform submissions.
 */
class WebformSubmissionsController extends ControllerBase {

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs a WebformSubmissionsController object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(EntityRepositoryInterface $entity_repository) {
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository')
    );
  }

  /**
   * Returns response for the source entity autocompletion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions.
   */
  public function sourceEntityAutocomplete(Request $request, WebformInterface $webform) {
    $match = $request->query->get('q');

    $webform_submission_storage = $this->entityTypeManager()->getStorage('webform_submission');
    $source_entities = $webform_submission_storage->getSourceEntities($webform);
    $matches = [];

    // @see \Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection::buildEntityQuery
    foreach ($source_entities as $source_entity_type => $source_entity_ids) {
      $definition = $this->entityTypeManager()->getDefinition($source_entity_type);
      $storage = $this->entityTypeManager()->getStorage($source_entity_type);

      if (empty($definition->getKey('id')) || empty($definition->getKey('label'))) {
        continue;
      }

      $query = $storage->getQuery();
      $query->range(0, 10);
      $query->condition($definition->getKey('id'), $source_entity_ids, 'IN');
      $query->condition($query->orConditionGroup()
        ->condition($definition->getKey('label'), $match, 'CONTAINS')
        ->condition($definition->getKey('id'), $match, 'CONTAINS')
      );
      $query->addTag($source_entity_type . '_access');
      $entity_ids = $query->execute();

      $entities = $storage->loadMultiple($entity_ids);
      foreach ($entities as $source_entity_id => $source_entity) {
        $label = Html::escape($this->entityRepository->getTranslationFromContext($source_entity)->label());
        $value = "$label ($source_entity_type:$source_entity_id)";
        $matches[] = [
          'value' => $value,
          'label' => $label,
        ];

        if (count($matches) === 10) {
          new JsonResponse($matches);
        }
      }
    }

    return new JsonResponse($matches);
  }

}
