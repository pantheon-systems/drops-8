<?php

namespace Drupal\config_update_ui\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Verify the config revert report and its links.
 *
 * @group config
 */
class ConfigUpdateTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * Use the Search module because it has two included config items in its
   * config/install, assuming node and user are also enabled.
   *
   * @var array
   */
  public static $modules = [
    'config',
    'config_update',
    'config_update_ui',
    'search',
    'node',
    'user',
    'block',
    'text',
    'field',
    'filter',
  ];

  /**
   * The admin user that will be created.
   *
   * @var object
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create user and log in.
    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer search',
      'view config updates report',
      'synchronize configuration',
      'export configuration',
      'import configuration',
      'revert configuration',
      'delete configuration',
      'administer filters',
    ]);
    $this->drupalLogin($this->adminUser);

    // Make sure local tasks and page title are showing.
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('page_title_block');

    // Load the Drush include file so that its functions can be tested, plus
    // the Drush testing include file.
    module_load_include('inc', 'config_update_ui', 'config_update_ui.drush_testing');
    module_load_include('inc', 'config_update_ui', 'config_update_ui.drush');
  }

  /**
   * Tests the config report and its linked pages.
   */
  public function testConfigReport() {
    // Test links to report page.
    $this->drupalGet('admin/config/development/configuration');
    $this->clickLink('Updates report');
    $this->assertNoReport();

    // Verify the Drush list types command.
    $output = implode("\n", drush_config_update_ui_config_list_types());
    $this->assertTrue(strpos($output, 'search_page') !== FALSE);
    $this->assertTrue(strpos($output, 'node_type') !== FALSE);
    $this->assertTrue(strpos($output, 'user_role') !== FALSE);
    $this->assertTrue(strpos($output, 'block') !== FALSE);

    // Verify some empty reports.
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->assertReport('Search page', [], [], [], []);
    $this->assertDrushReports('type', 'search_page', [], [], [], []);

    // Module, theme, and profile reports have no 'added' section.
    $this->drupalGet('admin/config/development/configuration/report/module/search');
    $this->assertReport('Search module', [], [], [], [], ['added']);
    $this->assertDrushReports('module', 'search', [], [], [], []);
    $this->drupalGet('admin/config/development/configuration/report/theme/classy');
    $this->assertReport('Classy theme', [], [], [], [], ['added']);
    $this->assertDrushReports('theme', 'classy', [], [], [], []);

    $inactive = ['locale.settings' => 'Simple configuration'];
    $this->drupalGet('admin/config/development/configuration/report/profile');
    $this->assertReport('Testing profile', [], [], [], $inactive, ['added']);
    // The locale.settings line should show that the Testing profile is the
    // provider.
    $this->assertText('Testing profile');
    $this->assertDrushReports('profile', '', [], [], [], array_keys($inactive));

    // Delete the user search page from the search UI and verify report for
    // both the search page config type and user module.
    $this->drupalGet('admin/config/search/pages');
    $this->clickLink('Delete');
    $this->drupalPostForm(NULL, [], 'Delete');
    $inactive = ['search.page.user_search' => 'Users'];
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->assertReport('Search page', [], [], [], $inactive);
    // The search.page.user_search line should show that the User module is the
    // provider.
    $this->assertText('User module');
    $this->assertDrushReports('type', 'search_page', [], [], [], array_keys($inactive));

    $this->drupalGet('admin/config/development/configuration/report/module/user');
    $this->assertReport('User module', [], [], [], $inactive, ['added', 'changed']);
    $this->assertDrushReports('module', 'user', [], [], [],
      [
        'rdf.mapping.user.user',
        'search.page.user_search',
        'views.view.user_admin_people',
        'views.view.who_s_new',
        'views.view.who_s_online',
      ], ['changed']);

    // Use the import link to get it back. Do this from the search page
    // report to make sure we are importing the right config.
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->clickLink('Import from source');
    $this->assertText('The configuration was imported');
    $this->assertNoReport();
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->assertReport('Search page', [], [], [], []);
    $this->assertDrushReports('type', 'search_page', [], [], [], []);

    // Verify that after import, there is no config hash generated.
    $this->drupalGet('admin/config/development/configuration/single/export/search_page/user_search');
    $this->assertText('id: user_search');
    $this->assertNoText('default_config_hash:');

    // Test importing again, this time using the Drush import command.
    $this->drupalGet('admin/config/search/pages');
    $this->clickLink('Delete');
    $this->drupalPostForm(NULL, [], 'Delete');
    $inactive = ['search.page.user_search' => 'Users'];
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->assertReport('Search page', [], [], [], $inactive);
    drush_config_update_ui_config_import_missing('search.page.user_search');
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->assertReport('Search page', [], [], [], []);

    // Edit the node search page from the search UI and verify report.
    $this->drupalGet('admin/config/search/pages');
    $this->clickLink('Edit');
    $this->drupalPostForm(NULL, [
      'label' => 'New label',
      'path'  => 'new_path',
    ], 'Save search page');
    $changed = ['search.page.node_search' => 'New label'];
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->assertReport('Search page', [], [], $changed, []);
    $this->assertDrushReports('type', 'search_page', [], [], array_keys($changed), []);

    // Test the show differences link.
    $this->clickLink('Show differences');
    $this->assertText('Content');
    $this->assertText('New label');
    $this->assertText('node');
    $this->assertText('new_path');

    // Test the show differences Drush command.
    $output = drush_config_update_ui_config_diff('search.page.node_search');
    $this->assertTrue(strpos($output, 'Content') !== FALSE);
    $this->assertTrue(strpos($output, 'New label') !== FALSE);
    $this->assertTrue(strpos($output, 'node') !== FALSE);
    $this->assertTrue(strpos($output, 'new_path') !== FALSE);

    // Test the Back link.
    $this->clickLink("Back to 'Updates report' page.");
    $this->assertNoReport();

    // Test the export link.
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->clickLink('Export');
    $this->assertText('Here is your configuration:');
    $this->assertText('id: node_search');
    $this->assertText('New label');
    $this->assertText('path: new_path');
    $this->assertText('search.page.node_search.yml');

    // Grab the uuid and hash lines for the next test.
    $text = $this->getTextContent();
    $matches = [];
    preg_match('|^.*uuid:.*$|m', $text, $matches);
    $uuid_line = $matches[0];
    preg_match('|^.*default_config_hash:.*$|m', $text, $matches);
    $hash_line = $matches[0];

    // Test reverting.
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->clickLink('Revert to source');
    $this->assertText('Are you sure you want to revert');
    $this->assertText('Search page');
    $this->assertText('node_search');
    $this->assertText('Customizations will be lost. This action cannot be undone');
    $this->drupalPostForm(NULL, [], 'Revert');
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->assertReport('Search page', [], [], [], []);

    // Verify that the uuid and hash keys were retained in the revert.
    $this->drupalGet('admin/config/development/configuration/single/export/search_page/node_search');
    $this->assertText('id: node_search');
    $this->assertText($uuid_line);
    $this->assertText($hash_line);

    // Test reverting again, this time using Drush single revert command.
    $this->drupalGet('admin/config/search/pages');
    $this->clickLink('Edit');
    $this->drupalPostForm(NULL, [
      'label' => 'New label',
      'path'  => 'new_path',
    ], 'Save search page');
    $changed = ['search.page.node_search' => 'New label'];
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->assertReport('Search page', [], [], $changed, []);
    drush_config_update_ui_config_revert('search.page.node_search');
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->assertReport('Search page', [], [], [], []);

    // Test reverting again, this time using Drush multiple revert command.
    $this->drupalGet('admin/config/search/pages');
    $this->clickLink('Edit');
    $this->drupalPostForm(NULL, [
      'label' => 'New label',
      'path'  => 'new_path',
    ], 'Save search page');
    $changed = ['search.page.node_search' => 'New label'];
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->assertReport('Search page', [], [], $changed, []);
    drush_config_update_ui_config_revert_multiple('type', 'search_page');
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->assertReport('Search page', [], [], [], []);

    // Add a new search page from the search UI and verify report.
    $this->drupalPostForm('admin/config/search/pages', [
      'search_type' => 'node_search',
    ], 'Add search page');
    $this->drupalPostForm(NULL, [
      'label' => 'test',
      'id'    => 'test',
      'path'  => 'test',
    ], 'Save');
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $added = ['search.page.test' => 'test'];
    $this->assertReport('Search page', [], $added, [], []);
    $this->assertDrushReports('type', 'search_page', [], array_keys($added), [], []);

    // Test the export link.
    $this->clickLink('Export');
    $this->assertText('Here is your configuration:');
    $this->assertText('id: test');
    $this->assertText('label: test');
    $this->assertText('path: test');
    $this->assertText('search.page.test.yml');

    // Test the delete link.
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->clickLink('Delete');
    $this->assertText('Are you sure');
    $this->assertText('cannot be undone');
    $this->drupalPostForm(NULL, [], 'Delete');
    $this->assertText('The configuration was deleted');
    // And verify the report again.
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->assertReport('Search page', [], [], [], []);

    // Change the search module config and verify the actions work for
    // simple config.
    $this->drupalPostForm('admin/config/search/pages', [
      'minimum_word_size' => 4,
    ], 'Save configuration');
    $changed = ['search.settings' => 'search.settings'];
    $this->drupalGet('admin/config/development/configuration/report/module/search');
    $this->assertReport('Search module', [], [], $changed, [], ['added']);

    $this->clickLink('Show differences');
    $this->assertText('Config difference for Simple configuration search.settings');
    $this->assertText('index::minimum_word_size');
    $this->assertText('4');

    $this->drupalGet('admin/config/development/configuration/report/module/search');
    $this->clickLink('Export');
    $this->assertText('minimum_word_size: 4');
    // Grab the hash line for the next test.
    $text = $this->getTextContent();
    $matches = [];
    preg_match('|^.*default_config_hash:.*$|m', $text, $matches);
    $hash_line = $matches[0];

    $this->drupalGet('admin/config/development/configuration/report/module/search');
    $this->clickLink('Revert to source');
    $this->drupalPostForm(NULL, [], 'Revert');

    // Verify that the hash was retained in the revert.
    $this->drupalGet('admin/config/development/configuration/single/export/system.simple/search.settings');
    $this->assertText($hash_line);

    $this->drupalGet('admin/config/development/configuration/report/module/search');
    $this->assertReport('Search module', [], [], [], [], ['added']);

    // Edit the plain_text filter from the filter UI and verify report.
    // The filter_format config type uses a label key other than 'label'.
    $this->drupalPostForm('admin/config/content/formats/manage/plain_text', [
      'name' => 'New label',
    ], 'Save configuration');
    $changed = ['filter.format.plain_text' => 'New label'];
    $this->drupalGet('admin/config/development/configuration/report/type/filter_format');
    $this->assertReport('Text format', [], [], $changed, []);
  }

  /**
   * Asserts that the report page has the correct content.
   *
   * Assumes you are already on the report page.
   *
   * @param string $title
   *   Report title to check for.
   * @param string[] $missing
   *   Array of items that should be listed as missing, name => label.
   * @param string[] $added
   *   Array of items that should be listed as added, name => label.
   * @param string[] $changed
   *   Array of items that should be listed as changed, name => label.
   * @param string[] $inactive
   *   Array of items that should be listed as inactive, name => label.
   * @param string[] $skip
   *   Array of report sections to skip checking.
   */
  protected function assertReport($title, array $missing, array $added, array $changed, array $inactive, array $skip = []) {
    $this->assertText('Configuration updates report for ' . $title);
    $this->assertText('Generate new report');

    if (!in_array('missing', $skip)) {
      $this->assertText('Missing configuration items');
      if (count($missing)) {
        foreach ($missing as $name => $label) {
          $this->assertText($name);
          $this->assertText($label);
        }
        $this->assertNoText('None: all provided configuration items are in your active configuration.');
      }
      else {
        $this->assertText('None: all provided configuration items are in your active configuration.');
      }
    }

    if (!in_array('inactive', $skip)) {
      $this->assertText('Inactive optional items');
      if (count($inactive)) {
        foreach ($inactive as $name => $label) {
          $this->assertText($name);
          $this->assertText($label);
        }
        $this->assertNoText('None: all optional configuration items are in your active configuration.');
      }
      else {
        $this->assertText('None: all optional configuration items are in your active configuration.');
      }
    }

    if (!in_array('added', $skip)) {
      $this->assertText('Added configuration items');
      if (count($added)) {
        foreach ($added as $name => $label) {
          $this->assertText($name);
          $this->assertText($label);
        }
        $this->assertNoText('None: all active configuration items of this type were provided by modules, themes, or install profile.');
      }
      else {
        $this->assertText('None: all active configuration items of this type were provided by modules, themes, or install profile.');
      }
    }

    if (!in_array('changed', $skip)) {
      $this->assertText('Changed configuration items');
      if (count($changed)) {
        foreach ($changed as $name => $label) {
          $this->assertText($name);
          $this->assertText($label);
        }
        $this->assertNoText('None: no active configuration items differ from their current provided versions.');
      }
      else {
        $this->assertText('None: no active configuration items differ from their current provided versions.');
      }
    }
  }

  /**
   * Asserts that the Drush reports have the correct content.
   *
   * @param string $type
   *   Type of report to run (type, module, theme, etc.).
   * @param string $name
   *   Name of that type to run (e.g., module machine name).
   * @param string[] $missing
   *   Array of config items that should be listed as missing.
   * @param string[] $added
   *   Array of config items that should be listed as added.
   * @param string[] $changed
   *   Array of config items that should be listed as changed.
   * @param string[] $inactive
   *   Array of config items that should be listed as inactive.
   * @param string[] $skip
   *   Array of report sections to skip checking.
   */
  protected function assertDrushReports($type, $name, array $missing, array $added, array $changed, array $inactive, array $skip = []) {
    if (!in_array('missing', $skip)) {
      $output = drush_config_update_ui_config_missing_report($type, $name);
      $this->assertEqual(count($output), count($missing), 'Drush missing report has correct number of items');
      if (count($missing)) {
        foreach ($missing as $item) {
          $this->assertTrue(in_array($item, $output), "Item $item is in the Drush missing report");
        }
      }
    }

    if (!in_array('added', $skip) && $type == 'type') {
      $output = drush_config_update_ui_config_added_report($name);
      $this->assertEqual(count($output), count($added), 'Drush added report has correct number of items');
      if (count($added)) {
        foreach ($added as $item) {
          $this->assertTrue(in_array($item, $output), "Item $item is in the Drush added report");
        }
      }
    }

    if (!in_array('changed', $skip)) {
      $output = drush_config_update_ui_config_different_report($type, $name);
      $this->assertEqual(count($output), count($changed), 'Drush changed report has correct number of items');
      if (count($changed)) {
        foreach ($changed as $item) {
          $this->assertTrue(in_array($item, $output), "Item $item is in the Drush changed report");
        }
      }
    }

    if (!in_array('inactive', $skip)) {
      $output = drush_config_update_ui_config_inactive_report($type, $name);
      $this->assertEqual(count($output), count($inactive), 'Drush inactive report has correct number of items');
      if (count($inactive)) {
        foreach ($inactive as $item) {
          $this->assertTrue(in_array($item, $output), "Item $item is in the Drush inactive report");
        }
      }
    }
  }

  /**
   * Asserts that the report is not shown.
   *
   * Assumes you are already on the report form page.
   */
  protected function assertNoReport() {
    $this->assertText('Report type');
    $this->assertText('Full report');
    $this->assertText('Single configuration type');
    $this->assertText('Single module');
    $this->assertText('Single theme');
    $this->assertText('Installation profile');
    $this->assertText('Updates report');
    $this->assertNoText('Missing configuration items');
    $this->assertNoText('Added configuration items');
    $this->assertNoText('Changed configuration items');
    $this->assertNoText('Unchanged configuration items');

    // Verify that certain report links are shown or not shown. For extensions,
    // only extensions that have configuration should be shown.
    // Modules.
    $this->assertLink('Search');
    $this->assertLink('Field');
    $this->assertNoLink('Configuration Update Base');
    $this->assertNoLink('Configuration Update Reports');

    // Themes.
    $this->assertNoLink('Stark');
    $this->assertNoLink('Classy');

    // Profiles.
    $this->assertLink('Testing');

    // Configuration types.
    $this->assertLink('Everything');
    $this->assertLink('Simple configuration');
    $this->assertLink('Search page');
  }

}
