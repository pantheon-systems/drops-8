<?php

namespace Drupal\webform\Tests\Handler;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for webform handler plugin.
 *
 * @group Webform
 */
class WebformHandlerTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform', 'webform_test_handler'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_handler_test'];

  /**
   * Tests webform handler plugin.
   */
  public function testWebformHandler() {
    $this->drupalLogin($this->rootUser);

    // Get the webform test handler.
    /** @var \Drupal\webform\WebformInterface $webform_handler_test */
    $webform_handler_test = Webform::load('test_handler_test');

    // Check new submission plugin invoking.
    $this->drupalGet('webform/test_handler_test');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preCreate');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postCreate');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElements');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterForm');

    // Check validate submission plugin invoked and displaying an error.
    $this->postSubmission($webform_handler_test, ['element' => 'a value']);
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preCreate');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postCreate');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElements');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterForm');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:validateForm');
    $this->assertRaw('The element must be empty. You entered <em class="placeholder">a value</em>.');
    $this->assertNoRaw('One two one two this is just a test');

    // Check submit submission plugin invoking.
    $sid = $this->postSubmission($webform_handler_test);
    $webform_submission = WebformSubmission::load($sid);
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preCreate');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postCreate');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElements');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterForm');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:validateForm');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:submitForm');
    $this->assertRaw('One two one two this is just a test');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:confirmForm');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preSave');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postSave insert');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postLoad');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preprocessConfirmation');
    $this->assertRaw('<div class="webform-confirmation__message">::preprocessConfirmation</div>');

    // Check update submission plugin invoking.
    $this->drupalPostForm('/admin/structure/webform/manage/test_handler_test/submission/' . $sid . '/edit', [], t('Save'));
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postSave update');

    // Check delete submission plugin invoking.
    $this->drupalPostForm('/admin/structure/webform/manage/test_handler_test/submission/' . $sid . '/delete', [], t('Delete'));
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postLoad');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preDelete');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postDelete');
    $this->assertRaw('Submission #' . $webform_submission->serial() . ' has been deleted.');

    // Check configuration settings.
    $this->drupalPostForm('admin/structure/webform/manage/test_handler_test/handlers/test/edit', ['settings[message]' => '{message}'], t('Save'));
    $this->postSubmission($webform_handler_test);
    $this->assertRaw('{message}');

    // Check disabling a handler.
    $this->drupalPostForm('admin/structure/webform/manage/test_handler_test/handlers/test/edit', ['status' => FALSE], t('Save'));
    $this->drupalGet('webform/test_handler_test');
    $this->assertNoRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preCreate');
    $this->assertNoRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postCreate');
    $this->assertNoRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElements');
    $this->assertNoRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterForm');

    // Enable the handler and disable the saving of results.
    $this->drupalPostForm('admin/structure/webform/manage/test_handler_test/handlers/test/edit', ['status' => TRUE], t('Save'));
    $webform_handler_test->setSettings(['results_disabled' => TRUE]);
    $webform_handler_test->save();

    // Check webform disabled with saving of results is disabled and handler does
    // not process results.
    $this->drupalLogout();
    $this->drupalGet('webform/test_handler_test');
    $this->assertNoFieldByName('op', 'Submit');
    $this->assertNoRaw('This webform is not saving or handling any submissions. All submitted data will be lost.');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preCreate');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postCreate');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElements');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterForm');

    // Check admin can still post submission.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('webform/test_handler_test');
    $this->assertFieldByName('op', 'Submit');
    $this->assertRaw('This webform is currently not saving any submitted data.');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preCreate');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postCreate');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElements');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterForm');

    // Check submit submission plugin invoking when saving results is disabled.
    $webform_handler_test->setSetting('results_disabled', TRUE);
    $webform_handler_test->save();
    $this->postSubmission($webform_handler_test);
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preCreate');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postCreate');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElements');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterForm');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:validateForm');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:submitForm');
    $this->assertRaw('One two one two this is just a test');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:confirmForm');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preSave');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postSave insert');
    // Check that post load is not executed when saving results is disabled.
    $this->assertNoRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postLoad');

    /**************************************************************************/
    // Handler.
    /**************************************************************************/

    // Check update handler.
    $this->drupalPostForm('admin/structure/webform/manage/test_handler_test/handlers/test/edit', [], t('Save'));
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:updateHandler');
    $this->assertRaw('The webform handler was successfully updated.');

    // Check delete handler.
    $this->drupalPostForm('admin/structure/webform/manage/test_handler_test/handlers/test/delete', [], t('Delete'));
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:deleteHandler');

    // Check create handler.
    $this->drupalPostForm('admin/structure/webform/manage/test_handler_test/handlers/add/test_log', ['handler_id' => 'test'], t('Save'));
    $this->assertRaw('The webform handler was successfully added.');
    // @todo Determine why create message is not being displayed.
    // Ajax machine name callback could be causing the issue.
    // $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:createHandler');
  }

  /**
   * Tests webform handler element plugin.
   */
  public function testWebformHandlerElement() {
    $this->drupalLogin($this->rootUser);

    // Check CRUD methods invoked.
    $edit = [
      'elements' => "element:
  '#type': textfield
  '#title': 'Empty element'
  '#description': 'Entering any value will throw an error'",
    ];
    $this->drupalPostForm('admin/structure/webform/manage/test_handler_test', $edit, t('Save'));
    $this->assertNoRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:createElement');
    $this->assertNoRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:updateElement');
    $this->assertNoRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:deleteElement');

    // Check create element.
    $edit = [
      'elements' => "element:
  '#type': textfield
  '#title': 'Empty element'
  '#description': 'Entering any value will throw an error'
test:
  '#type': textfield",
    ];
    $this->drupalPostForm('admin/structure/webform/manage/test_handler_test', $edit, t('Save'));
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:createElement');
    $this->assertNoRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:updateElement');
    $this->assertNoRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:deleteElement');

    // Check update element.
    $edit = [
      'elements' => "element:
  '#type': textfield
  '#title': 'Empty element'
  '#description': 'Entering any value will throw an error'
test:
  '#type': textfield
  '#title': Test",
    ];
    $this->drupalPostForm('admin/structure/webform/manage/test_handler_test', $edit, t('Save'));
    $this->assertNoRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:createElement');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:updateElement');
    $this->assertNoRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:deleteElement');

    // Check delete element.
    $edit = [
      'elements' => "element:
  '#type': textfield
  '#title': 'Empty element'
  '#description': 'Entering any value will throw an error'",
    ];
    $this->drupalPostForm('admin/structure/webform/manage/test_handler_test', $edit, t('Save'));
    $this->assertNoRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:createElement');
    $this->assertNoRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:updateElement');
    $this->assertRaw('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:deleteElement');
  }

}
