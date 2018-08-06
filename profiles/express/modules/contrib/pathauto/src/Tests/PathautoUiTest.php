<?php

namespace Drupal\pathauto\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\pathauto\Entity\PathautoPattern;

/**
 * Test basic pathauto functionality.
 *
 * @group pathauto
 */
class PathautoUiTest extends WebTestBase {

  use PathautoTestHelperTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('pathauto', 'node');

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {inheritdoc}
   */
  function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));
    $this->drupalCreateContentType(array('type' => 'article'));

    // Allow other modules to add additional permissions for the admin user.
    $permissions = array(
      'administer pathauto',
      'administer url aliases',
      'create url aliases',
      'administer nodes',
      'bypass node access',
      'access content overview',
    );
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);
  }

  function testSettingsValidation() {
    $edit = array();
    $edit['max_length'] = 'abc';
    $edit['max_component_length'] = 'abc';
    $this->drupalPostForm('admin/config/search/path/settings', $edit, 'Save configuration');
    /*$this->assertText('The field Maximum alias length is not a valid number.');
    $this->assertText('The field Maximum component length is not a valid number.');*/
    $this->assertNoText('The configuration options have been saved.');

    $edit['max_length'] = '0';
    $edit['max_component_length'] = '0';
    $this->drupalPostForm('admin/config/search/path/settings', $edit, 'Save configuration');
    /*$this->assertText('The field Maximum alias length cannot be less than 1.');
    $this->assertText('The field Maximum component length cannot be less than 1.');*/
    $this->assertNoText('The configuration options have been saved.');

    $edit['max_length'] = '999';
    $edit['max_component_length'] = '999';
    $this->drupalPostForm('admin/config/search/path/settings', $edit, 'Save configuration');
    /*$this->assertText('The field Maximum alias length cannot be greater than 255.');
    $this->assertText('The field Maximum component length cannot be greater than 255.');*/
    $this->assertNoText('The configuration options have been saved.');

    $edit['max_length'] = '50';
    $edit['max_component_length'] = '50';
    $this->drupalPostForm('admin/config/search/path/settings', $edit, 'Save configuration');
    $this->assertText('The configuration options have been saved.');
  }

  function testPatternsWorkflow() {
    // Try to save an empty pattern, should not be allowed.
    $this->drupalGet('admin/config/search/path/patterns/add');
    $edit = array(
      'type' => 'canonical_entities:node',
    );
    $this->drupalPostAjaxForm(NULL, $edit, 'type');
    $edit += array(
      'bundles[page]' => TRUE,
      'label' => 'Page pattern',
      'id' => 'page_pattern',
    );
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText('Path pattern field is required.');
    $this->assertNoText('The configuration options have been saved.');

    // Try to save an invalid pattern.
    $edit += array(
      'pattern' => '[node:title]/[user:name]/[term:name]',
    );
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText('Path pattern is using the following invalid tokens: [user:name], [term:name].');
    $this->assertNoText('The configuration options have been saved.');

    $edit['pattern'] = '#[node:title]';
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText('The Path pattern is using the following invalid characters: #.');
    $this->assertNoText('The configuration options have been saved.');

    // Checking whitespace ending of the string.
    $edit['pattern'] = '[node:title] ';
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText('The Path pattern doesn\'t allow the patterns ending with whitespace.');
    $this->assertNoText('The configuration options have been saved.');

    // Fix the pattern, then check that it gets saved successfully.
    $edit['pattern'] = '[node:title]';
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText('Pattern Page pattern saved.');

    \Drupal::service('pathauto.generator')->resetCaches();

    // Create a node with pattern enabled and check if the pattern applies.
    $title = 'Page Pattern enabled';
    $alias = '/page-pattern-enabled';
    $node = $this->createNode(['title' => $title, 'type' => 'page']);
    $this->drupalGet($alias);
    $this->assertResponse(200);
    $this->assertEntityAlias($node, $alias);

    // Edit workflow, set a new label and weight for the pattern.
    $this->drupalPostForm('/admin/config/search/path/patterns', ['entities[page_pattern][weight]' => '4'], t('Save'));
    $this->clickLink(t('Edit'));
    $this->assertUrl('/admin/config/search/path/patterns/page_pattern');
    $this->assertFieldByName('pattern', '[node:title]');
    $this->assertFieldByName('label', 'Page pattern');
    $this->assertFieldChecked('edit-status');
    $this->assertLink(t('Delete'));

    $edit = array('label' => 'Test');
    $this->drupalPostForm('/admin/config/search/path/patterns/page_pattern', $edit, t('Save'));
    $this->assertText('Pattern Test saved.');
    // Check that the pattern weight did not change.
    $this->assertOptionSelected('edit-entities-page-pattern-weight', '4');

    // Disable workflow.
    $this->drupalGet('/admin/config/search/path/patterns');
    $this->assertNoLink(t('Enable'));
    $this->clickLink(t('Disable'));
    $this->assertUrl('/admin/config/search/path/patterns/page_pattern/disable');
    $this->drupalPostForm(NULL, [], t('Disable'));
    $this->assertText('Disabled pattern Test.');

    // Load the pattern from storage and check if its disabled.
    $pattern = PathautoPattern::load('page_pattern');
    $this->assertFalse($pattern->status());

    \Drupal::service('pathauto.generator')->resetCaches();

    // Create a node with pattern disabled and check that we have no new alias.
    $title = 'Page Pattern disabled';
    $node = $this->createNode(['title' => $title, 'type' => 'page']);
    $this->assertNoEntityAlias($node);

    // Enable workflow.
    $this->drupalGet('/admin/config/search/path/patterns');
    $this->assertNoLink(t('Disable'));
    $this->clickLink(t('Enable'));
    $this->assertUrl('/admin/config/search/path/patterns/page_pattern/enable');
    $this->drupalPostForm(NULL, [], t('Enable'));
    $this->assertText('Enabled pattern Test.');

    // Reload pattern from storage and check if its enabled.
    $pattern = PathautoPattern::load('page_pattern');
    $this->assertTrue($pattern->status());

    // Delete workflow.
    $this->drupalGet('/admin/config/search/path/patterns');
    $this->clickLink(t('Delete'));
    $this->assertUrl('/admin/config/search/path/patterns/page_pattern/delete');
    $this->assertText(t('This action cannot be undone.'));
    $this->drupalPostForm(NULL, [], t('Delete'));
    $this->assertText('The pathauto pattern Test has been deleted.');

    $this->assertFalse(PathautoPattern::load('page_pattern'));
  }

}
