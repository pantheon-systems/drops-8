<?php

namespace Drupal\redirect\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'link' field type for redirect source.
 *
 * @FieldType(
 *   id = "redirect_source",
 *   label = @Translation("Redirect source"),
 *   description = @Translation("Stores a redirect source"),
 *   default_widget = "redirect_source",
 *   default_formatter = "redirect_source",
 *   no_ui = TRUE
 * )
 */
class RedirectSourceItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['path'] = DataDefinition::create('string')
      ->setLabel(t('Path'));

    $properties['query'] = MapDataDefinition::create()
      ->setLabel(t('Query'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'path' => array(
          'description' => 'The source path',
          'type' => 'varchar',
          'length' => 2048,
        ),
        'query' => array(
          'description' => 'Serialized array of path queries',
          'type' => 'blob',
          'size' => 'big',
          'serialize' => TRUE,
        ),
      ),
      'indexes' => array(
        'path' => array(array('path', 50)),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    // Set random length for the path.
    $domain_length = mt_rand(7, 15);
    $random = new Random();

    $values['path'] = 'http://www.' . $random->word($domain_length);

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return $this->path === NULL || $this->path === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'path';
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    // Unserialize the values.
    // @todo The storage controller should take care of this, see
    //   SqlContentEntityStorage::loadFieldItems, see
    //   https://www.drupal.org/node/2414835
    if (isset($values['query']) && is_string($values['query'])) {
      $values['query'] = unserialize($values['query']);
    }
    parent::setValue($values, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    return Url::fromUri('base:' . $this->path, ['query' => $this->query]);
  }

}
