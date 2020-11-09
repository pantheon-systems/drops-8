<?php

namespace Drupal\Tests\metatag\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verify that the JSON output from core works as intended.
 *
 * @group panelizer_metatag
 */
class MetatagPanelizerTest extends BrowserTestBase {

  // Contains helper methods.
  use MetatagHelperTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    // Modules for core functionality.
    'node',
    'field',
    'field_ui',
    'user',

    // Contrib dependencies.
    'panelizer',
    'token',

    // This module.
    'metatag',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'bartik';

  /**
   * Create an entity, view its JSON output, confirm Metatag data exists.
   */
  public function testPanelizerMetatagPreRender() {
    /* @var\Drupal\node\NodeInterface $node */
    $title = 'Panelizer Metatag Test Title';
    $body = 'Testing JSON output for a content type';
    $node = $this->createContentTypeNode($title, $body);
    $url = $node->toUrl();

    // Initiate session with a user who can manage metatags.
    $permissions = ['administer node display', 'administer meta tags'];
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    // Load the node's page.
    $this->drupalPostForm(
      'admin/structure/types/manage/metatag_test/display',
      ['panelizer[enable]' => TRUE],
      'Save'
    );

    $this->drupalGet('admin/structure/types/manage/metatag_test/display');
    $this->assertSession()->checkboxChecked('panelizer[enable]');

    $this->drupalGet($url);
    $this->assertSession()->elementContains('css', 'title', $title . ' | Drupal');
    $xpath = $this->xpath("//link[@rel='canonical']");
    self::assertEquals((string) $xpath[0]->getAttribute('href'), $url->toString());
    self::assertEquals(count($xpath), 1);
  }

}
