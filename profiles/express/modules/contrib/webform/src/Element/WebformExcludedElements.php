<?php

namespace Drupal\webform\Element;

use Drupal\user\Entity\Role;
use Drupal\webform\Entity\Webform as WebformEntity;
use Drupal\webform\Utility\WebformArrayHelper;

/**
 * Provides a webform element for webform excluded elements.
 *
 * @FormElement("webform_excluded_elements")
 */
class WebformExcludedElements extends WebformExcludedBase {

  /**
   * Get header for the excluded tableselect element.
   *
   * @return array
   *   An array container the header for the excluded tableselect element.
   */
  public static function getWebformExcludedHeader() {
    $header = [];
    $header['title'] = [
      'data' => t('Title'),
    ];
    $header['name'] = [
      'data' => t('Name'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    $header['type'] = [
      'data' => t('Type'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    $header['private'] = [
      'data' => t('Private'),
    ];
    $header['access'] = [
      'data' => t('Access'),
    ];
    return $header;
  }

  /**
   * Get options for excluded tableselect element.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic element element.
   *
   * @return array
   *   An array of options containing title, name, and type of items for a
   *   tableselect element.
   */
  public static function getWebformExcludedOptions(array $element) {

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = WebformEntity::load($element['#webform_id']);

    $options = [];
    $elements = $webform->getElementsInitializedFlattenedAndHasValue();
    foreach ($elements as $key => $element) {
      if (!empty($element['#access_view_roles'])) {
        $roles = array_map(function ($item) {
          return $item->label();
        }, Role::loadMultiple($element['#access_view_roles']));
      }
      else {
        $roles = [];
      }

      $options[$key] = [
        'title' => $element['#admin_title'] ?:$element['#title'] ?: $key,
        'name' => $key,
        'type' => isset($element['#type']) ? $element['#type'] : '',
        'private' => empty($element['#private']) ? t('No') : t('Yes'),
        'access' => $roles ? WebformArrayHelper::toString($roles) : t('All roles'),
      ];
      if (!empty($element['#private']) || $roles) {
        $options[$key]['#attributes']['class'][] = 'color-warning';
      }
    }
    return $options;
  }

}
