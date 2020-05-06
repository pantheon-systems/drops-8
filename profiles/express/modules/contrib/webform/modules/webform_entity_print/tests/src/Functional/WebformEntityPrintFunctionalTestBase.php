<?php

namespace Drupal\Tests\webform_entity_print\Functional;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Webform entity print test base.
 */
abstract class WebformEntityPrintFunctionalTestBase extends WebformBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_print_test',
    'webform',
    'webform_entity_print',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Use test print engine.
    \Drupal::configFactory()
      ->getEditable('entity_print.settings')
      ->set('print_engines.pdf_engine', 'testprintengine')
      ->save();
  }

}
