<?php

namespace Drupal\Tests\webform\Functional\Settings;

use Drupal\webform\Entity\Webform;
use Drupal\Tests\webform_node\Functional\WebformNodeBrowserTestBase;

/**
 * Tests for webform submission form unique limit.
 *
 * @group Webform
 */
class WebformSettingsLimitUniqueTest extends WebformNodeBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform', 'webform_node'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [
    'test_form_limit_total_unique',
    'test_form_limit_user_unique',
  ];

  /**
   * Tests webform submission form unique limit.
   */
  public function testLimitUnique() {
    $webform_total_unique = Webform::load('test_form_limit_total_unique');
    $webform_user_unique = Webform::load('test_form_limit_user_unique');

    $user = $this->drupalCreateUser();
    $admin_user = $this->drupalCreateUser(['administer webform']);
    $manage_any_user = $this->drupalCreateUser(['view any webform submission', 'edit any webform submission']);
    $edit_any_user = $this->drupalCreateUser(['edit any webform submission']);
    $manage_own_user = $this->drupalCreateUser(['view own webform submission', 'edit own webform submission']);
    $edit_user_only = $this->drupalCreateUser(['edit own webform submission']);

    /**************************************************************************/
    // Total unique. (webform)
    /**************************************************************************/

    // Check that access is denied for anonymous users.
    $this->drupalGet('/webform/test_form_limit_total_unique');
    $this->assertResponse(403);
    $this->assertNoFieldByName('name', '');

    // Check that access is allowed for edit any submission user.
    $this->drupalLogin($manage_any_user);
    $this->drupalGet('/webform/test_form_limit_total_unique');
    $this->assertResponse(200);
    $this->assertFieldByName('name', '');

    // Check that name is empty for new submission for admin user.
    $this->drupalLogin($admin_user);
    $this->drupalGet('/webform/test_form_limit_total_unique');
    $this->assertFieldByName('name', '');

    // Check that 'Test' form is available and display a message.
    $this->drupalGet('/webform/test_form_limit_total_unique/test');
    $this->assertRaw(' The below webform has been prepopulated with custom/random test data. When submitted, this information <strong>will still be saved</strong> and/or <strong>sent to designated recipients</strong>');

    // Check that name is empty for new submission for root user.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/webform/test_form_limit_total_unique');
    $this->assertFieldByName('name', '');

    // Check that name is set to 'John Smith' and 'Submission information' is
    // visible for admin user.
    $this->drupalLogin($admin_user);
    $sid = $this->postSubmission($webform_total_unique, ['name' => 'John Smith']);
    $this->drupalGet('/webform/test_form_limit_total_unique');
    $this->assertFieldByName('name', 'John Smith');
    $this->assertRaw("<div><b>Submission ID:</b> $sid</div>");

    // Check that name is set to 'John Smith' and 'Submission information' is
    // visible for root user.
    $this->drupalGet('/webform/test_form_limit_total_unique');
    $this->assertFieldByName('name', 'John Smith');
    $this->assertRaw("<div><b>Submission ID:</b> $sid</div>");

    // Check that 'Test' form also has name set to 'John Smith'
    // and does not display a message.
    $this->drupalGet('/webform/test_form_limit_total_unique/test');
    $this->assertFieldByName('name', 'John Smith');
    $this->assertRaw("<div><b>Submission ID:</b> $sid</div>");
    $this->assertNoRaw(' The below webform has been prepopulated with custom/random test data. When submitted, this information <strong>will still be saved</strong> and/or <strong>sent to designated recipients</strong>');

    // Check that edit any submission user can access and edit.
    $this->drupalLogin($manage_any_user);
    $this->drupalGet('/webform/test_form_limit_total_unique');
    $this->assertFieldByName('name', 'John Smith');
    $this->assertRaw("<div><b>Submission ID:</b> $sid</div>");

    /**************************************************************************/
    // Total unique. (node)
    /**************************************************************************/

    $this->drupalLogout();

    // Create webform node.
    $node_total_unique = $this->createWebformNode('test_form_limit_total_unique');

    // Check that access is denied for anonymous users.
    $this->drupalGet('/webform/test_form_limit_total_unique');
    $this->assertResponse(403);
    $this->assertNoFieldByName('name', '');
    $this->drupalGet('/node/' . $node_total_unique->id());
    $this->assertResponse(403);

    // Check that access is denied for authenticated user.
    $this->drupalLogin($user);
    $this->drupalGet('/webform/test_form_limit_total_unique');
    $this->assertResponse(403);
    $this->assertNoFieldByName('name', '');
    $this->drupalGet('/node/' . $node_total_unique->id());
    $this->assertResponse(403);

    // Check that access is allowed for edit any submission user.
    $this->drupalLogin($manage_any_user);
    $this->drupalGet('/node/' . $node_total_unique->id());
    $this->assertResponse(200);
    $this->assertFieldByName('name', '');

    // Check that name is empty for new submission for admin user.
    $this->drupalLogin($admin_user);
    $this->drupalGet('/node/' . $node_total_unique->id());
    $this->assertFieldByName('name', '');

    // Check that name is set to 'John Lennon' and 'Submission information' is
    // visible for admin user.
    $sid = $this->postNodeSubmission($node_total_unique, ['name' => 'John Lennon']);
    $this->drupalGet('/webform/test_form_limit_total_unique');
    $this->assertFieldByName('name', 'John Lennon');
    $this->assertRaw("<div><b>Submission ID:</b> $sid</div>");

    // Check that 'Test' form also has name set to 'John Lennon'
    // and does not display a message.
    $this->drupalGet('/node/' . $node_total_unique->id() . '/webform/test');
    $this->assertFieldByName('name', 'John Lennon');
    $this->assertRaw("<div><b>Submission ID:</b> $sid</div>");

    // Check that 'Test' form also has name set to 'John Lennon'
    // and does not display a message for root user.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/node/' . $node_total_unique->id() . '/webform/test');
    $this->assertFieldByName('name', 'John Lennon');
    $this->assertRaw("<div><b>Submission ID:</b> $sid</div>");

    /**************************************************************************/
    // User unique. (webform)
    /**************************************************************************/

    $this->drupalLogout();

    // Check that access is denied for anonymous users.
    $this->drupalGet('/webform/test_form_limit_user_unique');
    $this->assertResponse(403);

    // Check that access is denied for authenticated user.
    $this->drupalLogin($user);
    $this->drupalGet('/webform/test_form_limit_user_unique');
    $this->assertResponse(403);

    // Check that access is denied for edit any user.
    $this->drupalLogin($edit_any_user);
    $this->drupalGet('/webform/test_form_limit_user_unique');
    $this->assertResponse(403);

    // Check that access is denied for edit own user.
    $this->drupalLogin($edit_user_only);
    $this->drupalGet('/webform/test_form_limit_user_unique');
    $this->assertResponse(403);

    // Check that access is allowed for edit own submission user.
    $this->drupalLogin($manage_own_user);
    $this->drupalGet('/webform/test_form_limit_user_unique');
    $this->assertFieldByName('name', '');

    // Check that name is empty for new submission for admin user.
    $this->drupalLogin($admin_user);
    $this->drupalGet('/webform/test_form_limit_user_unique');
    $this->assertFieldByName('name', '');

    // Check that 'Test' form is available and display a message.
    $this->drupalGet('/webform/test_form_limit_user_unique/test');
    $this->assertRaw(' The below webform has been prepopulated with custom/random test data. When submitted, this information <strong>will still be saved</strong> and/or <strong>sent to designated recipients</strong>');

    // Check that name is empty for new submission for root user.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/webform/test_form_limit_user_unique');
    $this->assertFieldByName('name', '');

    // Check that name is set to 'John Smith' and 'Submission information' is
    // visible for admin user.
    $this->drupalLogin($admin_user);
    $sid = $this->postSubmission($webform_user_unique, ['name' => 'John Smith']);
    $this->drupalGet('/webform/test_form_limit_user_unique');
    $this->assertFieldByName('name', 'John Smith');
    $this->assertRaw("<div><b>Submission ID:</b> $sid</div>");

    // Check that 'Test' form also has name set to 'John Smith'
    // and does not display a message.
    $this->drupalGet('/webform/test_form_limit_user_unique/test');
    $this->assertFieldByName('name', 'John Smith');
    $this->assertRaw("<div><b>Submission ID:</b> $sid</div>");

    // Check that access is allowed for edit own submission user.
    $this->drupalLogin($manage_own_user);
    $this->drupalGet('/webform/test_form_limit_user_unique');
    $this->assertFieldByName('name', '');

    /**************************************************************************/

    // Check that name is still empty for new submission for root user.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/webform/test_form_limit_user_unique');
    $this->assertFieldByName('name', '');

    // Check that name is set to 'John Smith' and 'Submission information' is
    // visible for root user.
    $sid = $this->postSubmission($webform_user_unique, ['name' => 'Jane Doe']);
    $this->drupalGet('/webform/test_form_limit_user_unique');
    $this->assertFieldByName('name', 'Jane Doe');
    $this->assertRaw("<div><b>Submission ID:</b> $sid</div>");

    // Check that 'Test' form also has name set to 'Jane Doe'
    // and does not display a message.
    $this->drupalGet('/webform/test_form_limit_user_unique/test');
    $this->assertFieldByName('name', 'Jane Doe');
    $this->assertRaw("<div><b>Submission ID:</b> $sid</div>");
  }

}
