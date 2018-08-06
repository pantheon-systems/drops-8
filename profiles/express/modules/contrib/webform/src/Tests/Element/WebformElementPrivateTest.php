<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for webform element private.
 *
 * @group Webform
 */
class WebformElementPrivateTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_private'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create users.
    $this->createUsers();
  }

  /**
   * Test element access.
   */
  public function testElementAccess() {
    $webform = Webform::load('test_element_private');

    // Create a webform submission.
    $this->drupalLogin($this->normalUser);
    $this->postSubmission($webform);

    // Check element with #private property hidden for normal user.
    $this->drupalLogin($this->normalUser);
    $this->drupalGet('webform/test_element_private');
    $this->assertNoFieldByName('private', '');

    // Check element with #private property visible for admin user.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('webform/test_element_private');
    $this->assertFieldByName('private', '');
  }

}
