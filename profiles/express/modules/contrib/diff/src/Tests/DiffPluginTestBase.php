<?php

namespace Drupal\diff\Tests;

/**
 * Tests the Diff module plugins.
 *
 * @ingroup diff
 */
abstract class DiffPluginTestBase extends DiffTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'diff_test',
    'link',
    'options',
  ];

  /**
   * A storage instance for the entity form display.
   *
   * @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface
   */
  protected $formDisplay;

  /**
   * A storage instance for the entity view display.
   *
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   */
  protected $viewDisplay;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->formDisplay = \Drupal::entityTypeManager()->getStorage('entity_form_display');
    $this->viewDisplay = \Drupal::entityTypeManager()->getStorage('entity_view_display');

    $this->drupalLogin($this->rootUser);
  }

}
