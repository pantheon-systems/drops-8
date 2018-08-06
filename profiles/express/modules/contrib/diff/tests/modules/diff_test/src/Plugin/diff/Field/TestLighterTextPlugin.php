<?php

namespace Drupal\diff_test\Plugin\diff\Field;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\diff\FieldDiffBuilderBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Test diff builder with light weight.
 *
 * @FieldDiffBuilder(
 *   id = "test_lighter_text_plugin",
 *   label = @Translation("Test Lighter Text Plugin"),
 *   field_types = {
 *     "text",
 *   },
 *   weight = -20,
 * )
 */
class TestLighterTextPlugin extends FieldDiffBuilderBase {

  /**
   * {@inheritdoc}
   */
  public function build(FieldItemListInterface $field_items) {
    $result = [];

    // Every item from $field_items is of type FieldItemInterface.
    foreach ($field_items as $field_key => $field_item) {
      if (!$field_item->isEmpty()) {
        $values = $field_item->getValue();
        if (isset($values['value'])) {
          $result[$field_key][] = str_replace('applicable', 'lighter_test_plugin', $values['value']);
        }
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldStorageDefinitionInterface $field_definition) {
    return ($field_definition->getName() == 'test_field_lighter');
  }

}
