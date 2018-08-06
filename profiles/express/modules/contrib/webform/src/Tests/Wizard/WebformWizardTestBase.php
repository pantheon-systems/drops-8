<?php

namespace Drupal\webform\Tests\Wizard;

use Drupal\webform\Tests\WebformTestBase;

/**
 * Defines an abstract test base for webform wizard tests.
 */
abstract class WebformWizardTestBase extends WebformTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Exclude Progress tracker so that the default progress bar is displayed.
    // The default progress bar is most likely never going to change.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('libraries.excluded_libraries', ['progress-tracker'])
      ->save();
  }

  /**
   * Assert the current page using the progress bar's markup.
   *
   * @param string $title
   *   The title of the current page.
   * @param string $name
   *   The name (key) of the current page.
   */
  protected function assertCurrentPage($title, $name = NULL) {
    $this->assertPattern('|<li class="webform-progress-bar__page webform-progress-bar__page--current">\s+<b>' . $title . '</b>|');
    if ($name !== NULL) {
      $this->assertRaw('data-current-page="' . $name . '"');
    }
  }

}
