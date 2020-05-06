<?php

namespace Drupal\webform\Element;

use Drupal\Core\Form\OptGroup;
use Drupal\webform\Utility\WebformOptionsHelper;

/**
 * Trait for entity reference elements.
 */
trait WebformEntityTrait {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $info['#target_type'] = NULL;
    $info['#selection_handler'] = 'default';
    $info['#selection_settings'] = [];
    return $info;
  }

  /**
   * Set referencable entities as options for an element.
   *
   * @param array $element
   *   An element.
   * @param array $settings
   *   An array of settings used to limit and randomize options.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when the current user doesn't have access to the specified entity.
   *
   * @see \Drupal\system\Controller\EntityAutocompleteController
   */
  public static function setOptions(array &$element, array $settings = []) {
    if (!empty($element['#options'])) {
      return;
    }

    // Make sure #target_type is not empty.
    if (empty($element['#target_type'])) {
      $element['#options'] = [];
      return;
    }

    $selection_handler_options = [
      'target_type' => $element['#target_type'],
      'handler' => $element['#selection_handler'],
      'handler_settings' => (isset($element['#selection_settings'])) ? $element['#selection_settings'] : [],
      // Set '_webform_settings' used to limit and randomize options.
      // @see webform_query_entity_reference_alter()
      '_webform_settings' => $settings,
    ];

    // Make sure settings has a limit.
    $settings += ['limit' => 0];

    /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $selection_manager */
    $selection_manager = \Drupal::service('plugin.manager.entity_reference_selection');
    $handler = $selection_manager->getInstance($selection_handler_options);
    $referenceable_entities = $handler->getReferenceableEntities(NULL, 'CONTAINS', $settings['limit']);

    // Flatten all bundle grouping since they are not applicable to
    // WebformEntity elements.
    $options = [];
    foreach ($referenceable_entities as $bundle_options) {
      $options += $bundle_options;
    }

    // If the selection handler is not using views, then translate
    // the entity reference's options.
    if ($element['#selection_handler'] != 'views') {
      $options = self::translateOptions($options, $element);
    }

    if ($element['#type'] === 'webform_entity_select') {
      // Strip tags from options since <option> element does
      // not support HTML tags.
      $options = WebformOptionsHelper::stripTagsOptions($options);
    }
    else {
      // Only select menu can support optgroups.
      $options = OptGroup::flattenOptions($options);
    }

    // Issue #2826451: TermSelection returning HTML characters in select list.
    $options = WebformOptionsHelper::decodeOptions($options);

    $element['#options'] = $options;
  }

  /**
   * Translate the select options.
   *
   * @param array $options
   *   Untranslated options.
   * @param array $element
   *   An element.
   *
   * @return array
   *   Translated options.
   */
  protected static function translateOptions(array $options, array $element) {
    /** @var \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository */
    $entity_repository = \Drupal::service('entity.repository');

    foreach ($options as $key => $value) {
      if (is_array($value)) {
        $options[$key] = self::translateOptions($value, $element);
      }
      else {
        // Set the entity in the correct language for display.
        $option = \Drupal::entityTypeManager()
          ->getStorage($element['#target_type'])
          ->load($key);
        $option = $entity_repository->getTranslationFromContext($option);
        $options[$key] = $option->label();
      }
    }
    return $options;
  }

}
