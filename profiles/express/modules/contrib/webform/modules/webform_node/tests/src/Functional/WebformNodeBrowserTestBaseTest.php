<?php

namespace Drupal\Tests\webform_node\Functional;

/**
 * Test the webform node test base class.
 *
 * @group webform_browser
 */
class WebformNodeBrowserTestBaseTest extends WebformNodeBrowserTestBase {

  /**
   * Test base helper methods.
   */
  public function testWebformNodeBase() {
    $this->drupalLogin($this->rootUser);

    // Check WebformNodeBrowserTestBase::createWebformNode.
    $node = $this->createWebformNode('contact');
    $this->assertEquals('contact', $node->webform->target_id);

    // Check WebformNodeBrowserTestBase::postNodeSubmissionTest.
    $sid = $this->postNodeSubmissionTest($node);
    $this->assertEquals(1, $sid);
  }

}
