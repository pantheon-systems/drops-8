<?php

namespace Drupal\Tests\pathauto\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\pathauto\Entity\PathautoPattern;
use Drupal\Tests\pathauto\Functional\PathautoTestHelperTrait;

/**
 * Test basic pathauto functionality.
 *
 * @group pathauto
 */
class PathautoUiTest extends WebDriverTestBase {

  use PathautoTestHelperTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['pathauto', 'node', 'block'];

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
    $this->drupalCreateContentType(['type' => 'article']);

    // Allow other modules to add additional permissions for the admin user.
    $permissions = [
      'administer pathauto',
      'administer url aliases',
      'create url aliases',
      'administer nodes',
      'bypass node access',
      'access content overview',
    ];
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);
  }

  function testSettingsValidation() {
    $this->drupalGet('/admin/config/search/path/settings');

    $this->assertSession()->fieldExists('max_length');
    $this->assertSession()->elementAttributeContains('css', '#edit-max-length', 'min', '1');

    $this->assertSession()->fieldExists('max_component_length');
    $this->assertSession()->elementAttributeContains('css', '#edit-max-component-length', 'min', '1');
  }

  function testPatternsWorkflow() {
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block');

    $this->drupalGet('admin/config/search/path');
    $this->assertSession()->elementContains('css', '.block-local-tasks-block', 'Patterns');
    $this->assertSession()->elementContains('css', '.block-local-tasks-block', 'Settings');
    $this->assertSession()->elementContains('css', '.block-local-tasks-block', 'Bulk generate');
    $this->assertSession()->elementContains('css', '.block-local-tasks-block', 'Delete aliases');

    $this->drupalGet('admin/config/search/path/patterns');
    $this->clickLink('Add Pathauto pattern');

    $session = $this->getSession();
    $session->getPage()->fillField('type', 'canonical_entities:node');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $edit = [
      'type' => 'canonical_entities:node',
      'bundles[page]' => TRUE,
      'label' => 'Page pattern',
      'pattern' => '[node:title]/[user:name]/[term:name]',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    $this->assertSession()->waitForElementVisible('css', '[name="id"]');
    $edit += [
      'id' => 'page_pattern',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    $this->assertSession()->pageTextContains('Path pattern is using the following invalid tokens: [user:name], [term:name].');
    $this->assertSession()->pageTextNotContains('The configuration options have been saved.');

    // We do not need ID anymore, it is already set in previous step and made a label by browser
    unset($edit['id']);
    $edit['pattern'] = '#[node:title]';
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertSession()->pageTextContains('The Path pattern is using the following invalid characters: #.');
    $this->assertSession()->pageTextNotContains('The configuration options have been saved.');

    // Checking whitespace ending of the string.
    $edit['pattern'] = '[node:title] ';
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertSession()->pageTextContains('The Path pattern doesn\'t allow the patterns ending with whitespace.');
    $this->assertSession()->pageTextNotContains('The configuration options have been saved.');

    // Fix the pattern, then check that it gets saved successfully.
    $edit['pattern'] = '[node:title]';
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertSession()->pageTextContains('Pattern Page pattern saved.');

    \Drupal::service('pathauto.generator')->resetCaches();

    // Create a node with pattern enabled and check if the pattern applies.
    $title = 'Page Pattern enabled';
    $alias = '/page-pattern-enabled';
    $node = $this->createNode(['title' => $title, 'type' => 'page']);
    $this->drupalGet($alias);
    $this->assertSession()->pageTextContains($title);
    $this->assertEntityAlias($node, $alias);

    // Edit workflow, set a new label and weight for the pattern.
    $this->drupalGet('/admin/config/search/path/patterns');
    $session->getPage()->pressButton('Show row weights');
    $this->drupalPostForm(NULL, ['entities[page_pattern][weight]' => '4'], t('Save'));

    $session->getPage()->find('css', '.dropbutton-toggle > button')->press();
    $this->clickLink(t('Edit'));
    $destination_query = ['query' => ['destination' => Url::fromRoute('entity.pathauto_pattern.collection')->toString()]];
    $this->assertUrl('/admin/config/search/path/patterns/page_pattern', $destination_query);
    $this->assertFieldByName('pattern', '[node:title]');
    $this->assertFieldByName('label', 'Page pattern');
    $this->assertFieldChecked('edit-status');
    $this->assertLink(t('Delete'));

    $edit = ['label' => 'Test'];
    $this->drupalPostForm('/admin/config/search/path/patterns/page_pattern', $edit, t('Save'));
    $this->assertSession()->pageTextContains('Pattern Test saved.');
    // Check that the pattern weight did not change.
    $this->assertOptionSelected('edit-entities-page-pattern-weight', '4');

    $this->drupalGet('/admin/config/search/path/patterns/page_pattern/duplicate');
    $session->getPage()->pressButton('Edit');
    $edit = array('label' => 'Test Duplicate', 'id' => 'page_pattern_test_duplicate');
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertSession()->pageTextContains('Pattern Test Duplicate saved.');

    PathautoPattern::load('page_pattern_test_duplicate')->delete();

    // Disable workflow.
    $this->drupalGet('/admin/config/search/path/patterns');
    $session->getPage()->find('css', '.dropbutton-toggle > button')->press();
    $this->assertNoLink(t('Enable'));
    $this->clickLink(t('Disable'));
    $this->assertUrl('/admin/config/search/path/patterns/page_pattern/disable', $destination_query);
    $this->drupalPostForm(NULL, [], t('Disable'));
    $this->assertSession()->pageTextContains('Disabled pattern Test.');

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
    $this->assertUrl('/admin/config/search/path/patterns/page_pattern/enable', $destination_query);
    $this->drupalPostForm(NULL, [], t('Enable'));
    $this->assertSession()->pageTextContains('Enabled pattern Test.');

    // Reload pattern from storage and check if its enabled.
    $pattern = PathautoPattern::load('page_pattern');
    $this->assertTrue($pattern->status());

    // Delete workflow.
    $this->drupalGet('/admin/config/search/path/patterns');
    $session->getPage()->find('css', '.dropbutton-toggle > button')->press();
    $this->clickLink(t('Delete'));
    $this->assertUrl('/admin/config/search/path/patterns/page_pattern/delete', $destination_query);
    $this->assertSession()->pageTextContains(t('This action cannot be undone.'));
    $this->drupalPostForm(NULL, [], t('Delete'));
    $this->assertSession()->pageTextContains('The pathauto pattern Test has been deleted.');

    $this->assertFalse(PathautoPattern::load('page_pattern'));
  }

}
