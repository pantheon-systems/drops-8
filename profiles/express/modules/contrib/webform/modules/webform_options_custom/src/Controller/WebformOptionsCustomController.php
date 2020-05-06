<?php

namespace Drupal\webform_options_custom\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides route responses for webform options custom.
 */
class WebformOptionsCustomController extends ControllerBase {

  /**
   * Returns response for the webform options custom autocompletion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions.
   */
  public function autocomplete(Request $request) {
    $q = $request->query->get('q');

    $webform_options_custom_storage = $this->entityTypeManager()->getStorage('webform_options_custom');

    $query = $webform_options_custom_storage->getQuery()
      ->range(0, 10)
      ->sort('label');

    // Query title and id.
    $or = $query->orConditionGroup()
      ->condition('id', $q, 'CONTAINS')
      ->condition('label', $q, 'CONTAINS');
    $query->condition($or);

    $entity_ids = $query->execute();

    if (empty($entity_ids)) {
      return new JsonResponse([]);
    }
    $webform_options_custom = $webform_options_custom_storage->loadMultiple($entity_ids);

    $matches = [];
    foreach ($webform_options_custom as $webform_image) {
      $value = new FormattableMarkup('@label (@id)', ['@label' => $webform_image->label(), '@id' => $webform_image->id()]);
      $matches[] = ['value' => $value, 'label' => $value];
    }

    return new JsonResponse($matches);
  }

}
