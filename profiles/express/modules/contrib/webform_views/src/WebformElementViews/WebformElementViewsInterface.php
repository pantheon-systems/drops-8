<?php

namespace Drupal\webform_views\WebformElementViews;

use Drupal\webform\Plugin\WebformElementInterface;
use Drupal\webform\WebformInterface;

/**
 * Interface of webform element views handler.
 */
interface WebformElementViewsInterface {

  /**
   * Generate views data related to a given element of a given webform.
   *
   * @param array $element
   *   Webform element whose views data is queried
   * @param \Drupal\webform\WebformInterface $webform
   *   Webform within which the element is found
   *
   * @return array
   *   Views data array that corresponds to the provided webform element
   */
  public function getViewsData($element, WebformInterface $webform);

  /**
   * Generate views data definition that corresponds to given webform element.
   *
   * @param \Drupal\webform\Plugin\WebformElementInterface $element_plugin
   *   Webform element plugin whose views data definition is requested
   * @param array $element
   *   Webform element whose views data definition is requested
   *
   * @return array
   *   Views data definition array that corresponds to the given webform
   *   element. The structure of this array should have the following structure:
   *   - field: (array) Views data 'field' section to use for this webform
   *     element
   *   - filter: (array) Views data 'filter' section to use for this webform
   *     element
   *   - sort: (array) Views data 'sort' section to use for this webform element
   *   - TODO: Do you need more here?
   */
  public function getElementViewsData(WebformElementInterface $element_plugin, array $element);

}
