<?php

namespace Drupal\webform\Tests;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform submission webform.
 *
 * @group Webform
 */
class WebformSubmissionFormTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform', 'webform_test_validate'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_validate'];

  /**
   * Tests prepare elements.
   */
  public function testForm() {
    /* Test form#validate webform handling */
    $webform_validate = Webform::load('test_form_validate');
    $this->postSubmission($webform_validate, []);
    $this->assertRaw('Custom element is required.');

    $this->postSubmission($webform_validate, ['custom' => 'value']);
    $this->assertNoRaw('Custom element is required.');
  }

}
