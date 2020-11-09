<?php

namespace Drupal\Tests\metatag\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Ensures that meta tags are rendering correctly on forum pages.
 *
 * @group metatag
 */
class MetatagForumTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'token',
    'metatag',
    'node',
    'system',
    'forum',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Administrator user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Setup basic environment.
   */
  protected function setUp() {
    parent::setUp();

    $admin_permissions = [
      'administer nodes',
      'bypass node access',
      'administer meta tags',
      'administer site configuration',
      'access content',
    ];

    // Create and login user.
    $this->adminUser = $this->drupalCreateUser($admin_permissions);
    $this->drupalLogin($this->adminUser);

    // Create content type.
    $this->drupalCreateContentType(['type' => 'page', 'display_submitted' => FALSE]);
    $this->nodeId = $this->drupalCreateNode(
      [
        'title' => $this->randomMachineName(8),
        'promote' => 1,
      ])->id();

    $this->config('system.site')->set('page.front', '/node/' . $this->nodeId)->save();
  }

  /**
   * Verify that a forum post can be loaded when Metatag is enabled.
   */
  public function testForumPost() {
    $this->drupalGet('node/add/forum');
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'title[0][value]' => 'Testing forums',
      'taxonomy_forums' => 1,
      'body[0][value]' => 'Just testing.',
    ];
    $save_label = (floatval(\Drupal::VERSION) <= 8.3) ? $this->t('Save and publish') : $this->t('Save');
    $this->drupalPostForm(NULL, $edit, $save_label);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertText($this->t('@type @title has been created.', ['@type' => $this->t('Forum topic'), '@title' => 'Testing forums']));
  }

}
