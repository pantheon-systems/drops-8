<?php

namespace Drupal\webform\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\webform\Entity\WebformOptions;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformOptionsInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides route responses for webform options.
 */
class WebformOptionsController extends ControllerBase {

  /**
   * Returns response for the element autocompletion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param string $key
   *   Webform element key.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions.
   */
  public function autocomplete(Request $request, WebformInterface $webform, $key) {
    $q = $request->query->get('q');

    // Make sure the current user can access this webform.
    if (!$webform->access('view')) {
      return new JsonResponse([]);
    }

    // Get the webform element element.
    $elements = $webform->getElementsInitializedAndFlattened();
    if (!isset($elements[$key])) {
      return new JsonResponse([]);
    }

    // Get the element's webform options.
    $element = $elements[$key];
    $element['#options'] = $element['#autocomplete'];
    $options = WebformOptions::getElementOptions($element);
    if (empty($options)) {
      return new JsonResponse([]);
    }

    // Filter and convert options to autocomplete matches.
    $matches = [];
    $this->appendOptionsToMatchesRecursive($q, $options, $matches);
    return new JsonResponse($matches);
  }

  /**
   * Append webform options to autocomplete matches.
   *
   * @param string $q
   *   String to filter option's label by.
   * @param array $options
   *   An associative array of webform options.
   * @param array $matches
   *   An associative array of autocomplete matches.
   */
  protected function appendOptionsToMatchesRecursive($q, array $options, array &$matches) {
    foreach ($options as $value => $label) {
      if (is_array($label)) {
        $this->appendOptionsToMatchesRecursive($q, $label, $matches);
      }
      elseif (stripos($label, $q) !== FALSE) {
        $matches[] = [
          'value' => $value,
          'label' => $label,
        ];
      }
    }
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\webform\WebformOptionsInterface $webform_options
   *   The webform options.
   *
   * @return string
   *   The webform options label as a render array.
   */
  public function title(WebformOptionsInterface $webform_options) {
    return $webform_options->label();
  }

}
