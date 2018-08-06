<?php
  /**
   * @file
   * Contains \Drupal\pathologic\Tests\PathologicUITest.
   */

namespace Drupal\pathologic\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for the Pathologic UI.
 *
 * @group pathologic
 */
class PathologicUITest extends WebTestBase {

  public static $modules = ['pathologic', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));
    $this->drupalLogin($this->drupalCreateUser(array('administer filters', 'create page content')));
  }

  /**
   * Tests for the Pathologic UI.
   */
  public function testPathologicUi() {
    $this->doTestSettingsForm();
    $this->doTestFormatsOptions();
    $this->doTestFixUrl();
  }

  /**
   * Test settings form.
   */
  public function doTestSettingsForm() {
    $this->drupalGet('admin/config/content/pathologic');
    $this->assertText('Pathologic configuration');

    // Test submit form.
    $this->assertNoFieldChecked('edit-protocol-style-proto-rel');
    $edit = [
      'protocol_style' => 'proto-rel',
      'local_paths' => 'http://example.com/',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->assertText('The configuration options have been saved.');
    $this->assertFieldChecked('edit-protocol-style-proto-rel');
    $this->assertText('http://example.com/');
    $this->clickLink('Pathologic’s documentation');
    $this->assertResponse(200);
  }

  /**
   * Test text formats and editors options with pathologic.
   */
  public function doTestFormatsOptions() {

    // Test plain text with pathologic configuration.
    $this->drupalGet('/admin/config/content/formats/manage/plain_text');

    // Select pathologic option.
    $this->assertText('Correct URLs with Pathologic');
    $this->assertNoFieldChecked('edit-filters-filter-pathologic-status');
    $this->drupalPostForm(NULL, array(
      'filters[filter_html_escape][status]' => FALSE,
      'filters[filter_pathologic][status]' => '1',
    ), t('Save configuration'));

    $this->drupalGet('/admin/config/content/formats/manage/plain_text');
    $this->assertRaw('In most cases, Pathologic should be the <em>last</em> filter in the &ldquo;Filter processing order&rdquo; list.');
    $this->assertText('Select whether Pathologic should use the global Pathologic settings');
    $this->assertFieldChecked('edit-filters-filter-pathologic-status');
    $this->drupalPostForm(NULL, array(
      'filters[filter_pathologic][settings][settings_source]' => 'local',
      'filters[filter_pathologic][settings][local_settings][protocol_style]' => 'full',
      ), t('Save configuration'));

    $this->drupalGet('/admin/config/content/formats/manage/plain_text');
    $this->assertFieldChecked('edit-filters-filter-pathologic-settings-settings-source-local');
    $this->assertFieldChecked('edit-filters-filter-pathologic-settings-local-settings-protocol-style-full');
    $this->assertText('Custom settings for this text format');
  }

  /**
   * Test that a url is fixed with pathologic.
   */
  public function doTestFixUrl() {
    $this->drupalGet('node/add/page');
    $edit = array(
      'title[0][value]' => 'Test pathologic',
      'body[0][value]' => '<a href="node/1">Test link</a>',
    );
    $this->drupalPostForm('node/add/page', $edit, t('Save'));

    // Assert that the link is processed with Pathologic.
    $this->clickLink('Test link');
    $this->assertTitle('Test pathologic | Drupal');
  }

}
