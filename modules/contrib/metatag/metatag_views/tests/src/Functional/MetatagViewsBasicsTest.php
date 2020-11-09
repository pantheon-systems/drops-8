<?php

namespace Drupal\Tests\metatag_views\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Confirm the defaults functionality works.
 *
 * @group metatag
 */
class MetatagViewsBasicsTest extends BrowserTestBase {

  // Contains helper methods.
  use \Drupal\Tests\metatag\Functional\MetatagHelperTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    // Modules for core functionality.
    'block',
    'field',
    'field_ui',
    'help',
    'node',
    'user',

    // Views. Duh. Enable the Views UI so it can be fully tested.
    'views',
    'views_ui',

    // Contrib dependencies.
    'token',
    'metatag',

    // This module.
    'metatag_views',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'bartik';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Enable the Bartik theme and make it the default.
    // @todo remove this once 8.8 is required and $defaultTheme can be
    // relied upon.
    $theme = 'bartik';
    \Drupal::service('theme_installer')->install([$theme]);
    $this->config('system.theme')->set('default', $theme);

    // Place the local actions block in the theme so that we can assert the
    // presence of local actions and such.
    $this->drupalPlaceBlock('local_actions_block', [
      'region' => 'content',
      'theme' => $theme,
    ]);
  }

  /**
   * Confirm the Views functionality works, including UI.
   */
  public function testViewsUi() {
    // Load the front page.
    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);

    // With nothing else configured the front page just has a login form.
    $this->assertSession()->pageTextContains('Enter your Drupal username.');

    // Log in as user 1.
    $this->loginUser1();

    // Load the main Views admin page.
    $this->drupalGet('/admin/structure/views');
    $this->assertSession()->statusCodeEquals(200);

    // Enable the Archive view. This should be the first such link while the
    // gallery is the second.
    $this->clickLink('Enable', 0);

    // Confirm the archive page works.
    $this->drupalGet('/archive');
    $this->assertSession()->statusCodeEquals(200);

    // Confirm what the page title looks like by default.
    $this->assertSession()->titleEquals('Monthly archive | Drupal');

    // Load the Arcive view.
    $this->drupalGet('/admin/structure/views/view/archive');
    $this->assertSession()->statusCodeEquals(200);

    // Confirm that the Metatag options are present.
    $this->assertSession()->pageTextContains('Meta tags:');

    // Confirm that the page is currently using defaults.
    $this->assertSession()->pageTextContains('Using defaults');

    // Open the 'page' configuration.
    $this->clickLink('Page');

    // Confirm that no changes have been made yet.
    $this->assertNoText('Overridden');

    // Open the settings dialog.
    $this->clickLink('Using defaults');

    // Confirm the settings opened and it has some basic fields.
    $this->assertSession()->pageTextContains('Configure the meta tags below.');
    $this->assertFieldByName('title');
    $this->assertFieldByName('description');
    $this->assertFieldByName('op');
    $edit = [
      'title' => 'Metatag title',
      'description' => 'Metatag description.',
    ];
    $this->drupalPostForm(NULL, $edit, 'Apply');

    // Confirm the Metatag settings are now overridden.
    $this->assertSession()->pageTextContains('Overridden');

    // @todo Confirm there's now a "save" button.
    // Save the changes.
    $edit = [];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // @todo Confirm the page saved.
    // Load the archives page again.
    $this->drupalGet('/archive');
    $this->assertSession()->statusCodeEquals(200);

    // Confirm what the page title looks like now.
    $this->assertSession()->titleEquals('Metatag title');

    // Load the Metatag admin page to confirm it still works.
    $this->drupalGet('admin/config/search/metatag');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertLinkByHref('/admin/config/search/metatag/global');
    $this->assertLinkByHref('/admin/config/search/metatag/front');
    $this->assertLinkByHref('/admin/config/search/metatag/403');
    $this->assertLinkByHref('/admin/config/search/metatag/404');
    $this->assertLinkByHref('/admin/config/search/metatag/node');
    $this->assertLinkByHref('/admin/config/search/metatag/taxonomy_term');
    $this->assertLinkByHref('/admin/config/search/metatag/user');
  }

}
