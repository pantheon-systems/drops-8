<?php

namespace Drupal\Tests\webform_templates\Functional;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformInterface;

/**
 * Tests for webform submission webform settings.
 *
 * @group WebformTemplates
 */
class WebformTemplatesTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform', 'webform_templates'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_template'];

  /**
   * Tests webform templates.
   */
  public function testTemplates() {
    $user_account = $this->drupalCreateUser([
      'access webform overview',
      'administer webform',
    ]);

    $admin_account = $this->drupalCreateUser([
      'access webform overview',
      'administer webform',
      'administer webform templates',
    ]);

    // Login the user.
    $this->drupalLogin($user_account);

    $template_webform = Webform::load('test_form_template');

    // Check the templates always will remain closed.
    $this->assertTrue($template_webform->isClosed());
    $template_webform->setStatus(WebformInterface::STATUS_OPEN)->save();
    $this->assertTrue($template_webform->isClosed());

    // Check template is included in the 'Templates' list display.
    $this->drupalGet('/admin/structure/webform/templates');
    $this->assertRaw('Test: Webform: Template');
    $this->assertRaw('Test using a webform as a template.');

    // Check template is accessible to user with create webform access.
    $this->drupalGet('/webform/test_form_template');
    $this->assertResponse(200);
    $this->assertRaw('You are previewing the below template,');

    // Check select template clears the description.
    $this->drupalGet('/admin/structure/webform/manage/test_form_template/duplicate');
    $this->assertFieldByName('description[value]', '');

    // Check that admin can not access manage templates.
    $this->drupalGet('/admin/structure/webform/templates/manage');
    $this->assertResponse(403);

    // Login the admin.
    $this->drupalLogin($admin_account);

    // Check that admin can access manage templates.
    $this->drupalGet('/admin/structure/webform/templates/manage');
    $this->assertResponse(200);

    // Check select template clears the description.
    $this->drupalGet('/admin/structure/webform/manage/test_form_template/duplicate', ['query' => ['template' => 1]]);
    $this->assertFieldByName('description[value]', 'Test using a webform as a template.');
  }

}
