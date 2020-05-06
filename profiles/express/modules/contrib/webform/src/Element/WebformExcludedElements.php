<?php

namespace Drupal\webform\Element;

use Drupal\user\Entity\Role;
use Drupal\webform\Entity\Webform as WebformEntity;
use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\webform\Plugin\WebformElement\WebformActions as WebformActionsWebformElement;

/**
 * Provides a webform element for webform excluded elements.
 *
 * @FormElement("webform_excluded_elements")
 */
class WebformExcludedElements extends WebformExcludedBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + [
      '#exclude_markup' => TRUE,
    ];
  }

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
    $header['key'] = [
      'data' => t('Key'),
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
    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
    $element_manager = \Drupal::service('plugin.manager.webform.element');

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = WebformEntity::load($element['#webform_id'])
      ?: \Drupal::service('webform.request')->getCurrentWebform();

    $options = [];
    if ($element['#exclude_markup']) {
      $form_elements = $webform->getElementsInitializedFlattenedAndHasValue();
    }
    else {
      $form_elements = $webform->getElementsInitializedAndFlattened();
    }
    foreach ($form_elements as $key => $form_element) {
      $form_element_plugin = $element_manager->getElementInstance($form_element);
      // Skip markup elements that are containers or actions.
      if (!$element['#exclude_markup']
        && ($form_element_plugin->isContainer($form_element) || $form_element_plugin instanceof WebformActionsWebformElement)) {
        continue;
      }

      if (!empty($form_element['#access_view_roles'])) {
        $roles = array_map(function ($item) {
          return $item->label();
        }, Role::loadMultiple($form_element['#access_view_roles']));
      }
      else {
        $roles = [];
      }

      $options[$key] = [
        'title' => $form_element['#admin_title'] ?:$form_element['#title'] ?: $key,
        'key' => $key,
        'type' => isset($form_element['#type']) ? $form_element['#type'] : '',
        'private' => empty($form_element['#private']) ? t('No') : t('Yes'),
        'access' => $roles ? WebformArrayHelper::toString($roles) : t('All roles'),
      ];
      if (!empty($form_element['#private']) || $roles) {
        $options[$key]['#attributes']['class'][] = 'color-warning';
      }
    }
    return $options;
  }

}
