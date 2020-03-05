<?php

namespace Drupal\pathauto_string_id_test\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines a test entity with a string ID.
 *
 * @ContentEntityType(
 *   id = "pathauto_string_id_test",
 *   label = @Translation("Test entity with string ID"),
 *   handlers = {
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "pathauto_string_id_test",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *   },
 *   links = {
 *     "canonical" = "/pathauto_string_id_test/{pathauto_string_id_test}",
 *   },
 * )
 */
class PathautoStringIdTest extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('string')
      ->setLabel('ID')
      ->setReadOnly(TRUE)
      // A bigger value will not be allowed to build the index.
      ->setSetting('max_length', 191);
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel('Name');
    $fields['path'] = BaseFieldDefinition::create('path')
      ->setLabel('Path')
      ->setComputed(TRUE);

    return $fields;
  }

}
