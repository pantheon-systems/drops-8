<?php

namespace Drupal\Tests\metatag_views\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\metatag\Functional\MetatagHelperTrait;

/**
 * Confirm the defaults functionality works.
 *
 * @group panelizer
 */
class MetatagViewsBasicsTest extends BrowserTestBase {

  use MetatagHelperTrait;

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
  protected function setUp() {
    parent::setUp();

    // Enable the Bartik theme and make it the default.
    $theme = 'bartik';
    \Drupal::service('theme_installer')->install([$theme]);
    \Drupal::service('theme_handler')->setDefault($theme);

    // Place the local actions block in the theme so that we can assert the
    // presence of local actions and such.
    $this->drupalPlaceBlock('local_actions_block', [
      'region' => 'content',
      'theme' => $theme,
    ]);
  }

  /**
   * Confirm the site isn't broken.
   */
  public function testSiteStillWorks() {
    // Load the front page.
    $this->drupalGet('<front>');
    $this->assertResponse(200);

    // With nothing else configured the front page just has a login form.
    $this->assertText('Enter your Drupal username.');

    // Log in as user 1.
    $this->loginUser1();

    // Load the main Views admin page.
    $this->drupalGet('/admin/structure/views');
    $this->assertResponse(200);

    // Enable the Archive view. This should be the first such link while the
    // gallery is the second.
    $this->clickLink('Enable', 0);

    // Confirm the archive page works.
    $this->drupalGet('/archive');
    $this->assertResponse(200);

    // Confirm what the page title looks like by default.
    $this->assertTitle('Monthly archive | Drupal');

    // Load the Arcive view.
    $this->drupalGet('/admin/structure/views/view/archive');
    $this->assertResponse(200);

    // Confirm that the Metatag options are present.
    $this->assertText('Meta tags:');

    // Confirm that the page is currently using defaults.
    $this->assertText('Using defaults');

    // Open the 'page' configuration.
    $this->clickLink('Page');

    // Confirm that no changes have been made yet.
    $this->assertNoText('Overridden');

    // Open the settings dialog.
    $this->clickLink('Using defaults');

    // Confirm the settings opened and it has some basic fields.
    $this->assertText('Configure the meta tags below.');
    $this->assertFieldByName('title');
    $this->assertFieldByName('description');
    $this->assertFieldByName('op');//, 'Apply');
    $edit = [
      'title' => 'Metatag title',
      'description' => 'Metatag description.',
    ];
    $this->drupalPostForm(NULL, $edit, 'Apply');

    // Confirm the Metatag settings are now overridden.
    $this->assertText('Overridden');

    // @todo Confirm there's now a "save" button.
    // $this->assertFieldByName('op');//, 'Save');

    // Save the changes.
    $edit = [];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // @todo Confirm the page saved.

    // Load the archives page again.
    $this->drupalGet('/archive');
    $this->assertResponse(200);

    // Confirm what the page title looks like now.
    $this->assertTitle('Metatag title');
  }

}
