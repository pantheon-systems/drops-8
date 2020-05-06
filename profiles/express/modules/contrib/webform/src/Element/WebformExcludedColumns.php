<?php

namespace Drupal\webform\Element;

use Drupal\webform\Entity\Webform as WebformEntity;

/**
 * Provides a webform element for webform excluded columns (submission field and elements).
 *
 * @FormElement("webform_excluded_columns")
 */
class WebformExcludedColumns extends WebformExcludedBase {

  /**
   * {@inheritdoc}
   */
  public static function getWebformExcludedHeader() {
    return [
      'title' => t('Title'),
      'name' => t('Name'),
      'type' => t('Date type/Element type'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getWebformExcludedOptions(array $element) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = WebformEntity::load($element['#webform_id'])
      ?: \Drupal::service('webform.request')->getCurrentWebform();

    $options = [];

    /** @var \Drupal\webform\WebformSubmissionStorageInterface $submission_storage */
    $submission_storage = \Drupal::entityTypeManager()->getStorage('webform_submission');
    $field_definitions = $submission_storage->getFieldDefinitions();
    $field_definitions = $submission_storage->checkFieldDefinitionAccess($webform, $field_definitions);
    foreach ($field_definitions as $key => $field_definition) {
      $options[$key] = [
        'title' => $field_definition['title'],
        'name' => $key,
        'type' => $field_definition['type'],
      ];
    }
    $elements = $webform->getElementsInitializedFlattenedAndHasValue('view');

    // Replace tokens which can be used in an element's #title.
    /** @var \Drupal\webform\WebformTokenManagerInterface $token_manager */
    $token_manager = \Drupal::service('webform.token_manager');
    $elements = $token_manager->replace($elements, $webform);

    foreach ($elements as $key => $element) {
      $options[$key] = [
        'title' => $element['#admin_title'] ?:$element['#title'] ?: $key,
        'name' => $key,
        'type' => isset($element['#type']) ? $element['#type'] : '',
      ];
    }
    return $options;
  }

}
