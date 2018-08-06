<?php

namespace Drupal\entity_module_bundle_plugin_examples_test\Plugin\BundlePluginTest;

use Drupal\entity\BundleFieldDefinition;
use Drupal\Core\Plugin\PluginBase;
use Drupal\entity_module_bundle_plugin_test\Plugin\BundlePluginTest\BundlePluginTestInterface;

/**
 * Provides the second bundle plugin.
 *
 * @BundlePluginTest(
 *   id = "second",
 *   label = @Translation("Second"),
 * )
 */
class Second extends PluginBase implements BundlePluginTestInterface {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = [];
    $fields['second_mail'] = BundleFieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setRequired(TRUE);

    return $fields;
  }

}
