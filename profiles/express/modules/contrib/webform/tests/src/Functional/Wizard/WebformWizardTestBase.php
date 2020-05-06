<?php

namespace Drupal\Tests\webform\Functional\Wizard;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Defines an abstract test base for webform wizard tests.
 */
abstract class WebformWizardTestBase extends WebformBrowserTestBase {

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
   *   The title of the page.
   * @param string $page
   *   The name (key) of the current page.
   */
  protected function assertCurrentPage($title, $page) {
    $this->assertPattern('|<li data-webform-page="' . $page . '" class="webform-progress-bar__page webform-progress-bar__page--current">\s+<b>' . $title . '</b>|');
  }

}
