<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform element private.
 *
 * @group Webform
 */
class WebformElementPrivateTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_private'];

  /**
   * Test element access.
   */
  public function testElementAccess() {
    $normal_user = $this->drupalCreateUser(['view own webform submission']);

    $webform = Webform::load('test_element_private');

    /**************************************************************************/

    // Login as normal user.
    $this->drupalLogin($normal_user);

    // Create two webform submissions.
    $this->postSubmission($webform);
    $sid = $this->postSubmission($webform);

    // Check element with #private property hidden for normal user.
    $this->drupalGet('/webform/test_element_private');
    $this->assertNoFieldByName('private', '');

    // Check submission data with #private property hidden for normal user.
    $this->drupalGet("/webform/test_element_private/submissions/$sid");
    $this->assertNoCssSelect('#test_element_private--private');
    $this->assertNoRaw('<label>private</label>');

    // Check user submissions columns excludes 'private' column.
    $this->drupalGet('/webform/test_element_private/submissions');
    $this->assertNoRaw('<th specifier="element__private">');

    // Login as root user.
    $this->drupalLogin($this->rootUser);

    // Check element with #private property visible for admin user.
    $this->drupalGet('/webform/test_element_private');
    $this->assertFieldByName('private', '');

    // Check submission data with #private property visible for admin user.
    $this->drupalGet("/webform/test_element_private/submissions/$sid");
    $this->assertCssSelect('#test_element_private--private');
    $this->assertRaw('<label>private</label>');

    // Check user submissions columns include 'private' column.
    $this->drupalGet('/webform/test_element_private/submissions');
    $this->assertRaw('<th specifier="element__private">');
  }

}
