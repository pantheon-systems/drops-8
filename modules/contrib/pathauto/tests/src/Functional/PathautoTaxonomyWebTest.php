<?php

namespace Drupal\Tests\pathauto\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests pathauto taxonomy UI integration.
 *
 * @group pathauto
 */
class PathautoTaxonomyWebTest extends BrowserTestBase {

  use PathautoTestHelperTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['taxonomy', 'pathauto', 'views'];

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

    // Allow other modules to add additional permissions for the admin user.
    $permissions = [
      'administer pathauto',
      'administer url aliases',
      'create url aliases',
      'administer taxonomy',
    ];
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);

    $this->createPattern('taxonomy_term', '/[term:vocabulary]/[term:name]');
  }

  /**
   * Basic functional testing of Pathauto with taxonomy terms.
   */
  function testTermEditing() {
    $this->drupalGet('admin/structure');
    $this->drupalGet('admin/structure/taxonomy');

    // Add vocabulary "tags".
    $vocabulary = $this->addVocabulary(['name' => 'tags', 'vid' => 'tags']);

    // Create term for testing.
    $name = 'Testing: term name [';
    $automatic_alias = '/tags/testing-term-name';
    $this->drupalPostForm('admin/structure/taxonomy/manage/tags/add', ['name[0][value]' => $name], 'Save');
    $name = trim($name);
    $this->assertSession()->pageTextContains("Created new term $name.");
    $term = $this->drupalGetTermByName($name);

    // Look for alias generated in the form.
    $this->drupalGet("taxonomy/term/{$term->id()}/edit");
    $this->assertFieldChecked('edit-path-0-pathauto');
    $this->assertFieldByName('path[0][alias]', $automatic_alias, 'Generated alias visible in the path alias field.');

    // Check whether the alias actually works.
    $this->drupalGet($automatic_alias);
    $this->assertText($name, 'Term accessible through automatic alias.');

    // Manually set the term's alias.
    $manual_alias = '/tags/' . $term->id();
    $edit = [
      'path[0][pathauto]' => FALSE,
      'path[0][alias]' => $manual_alias,
    ];
    $this->drupalPostForm("taxonomy/term/{$term->id()}/edit", $edit, t('Save'));
    $this->assertText("Updated term $name.");

    // Check that the automatic alias checkbox is now unchecked by default.
    $this->drupalGet("taxonomy/term/{$term->id()}/edit");
    $this->assertNoFieldChecked('edit-path-0-pathauto');
    $this->assertFieldByName('path[0][alias]', $manual_alias);

    // Submit the term form with the default values.
    $this->drupalPostForm(NULL, ['path[0][pathauto]' => FALSE], t('Save'));
    $this->assertText("Updated term $name.");

    // Test that the old (automatic) alias has been deleted and only accessible
    // through the new (manual) alias.
    $this->drupalGet($automatic_alias);
    $this->assertResponse(404, 'Term not accessible through automatic alias.');
    $this->drupalGet($manual_alias);
    $this->assertText($name, 'Term accessible through manual alias.');
  }

}
