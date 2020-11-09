<?php

namespace Drupal\Tests\metatag_page_manager\Functional;

use Drupal\page_manager\Entity\Page;
use Drupal\page_manager\Entity\PageVariant;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\metatag\Functional\MetatagHelperTrait;

/**
 * Confirm the Page Manager integration works.
 *
 * @group metatag
 */
class MetatagPageManagerTest extends BrowserTestBase {

  use MetatagHelperTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    // This module.
    'metatag_page_manager',
    'page_manager_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The assert session object.
   *
   * @var \Drupal\Tests\WebAssert
   */
  public $assertSession;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->assertSession = $this->assertSession();

    Page::create([
      'id' => 'metatag_page_manager_test',
      'label' => 'Metatag Page',
      'path' => 'metatag-test',
    ])->save();
    PageVariant::create([
      'id' => 'metatag_page_manager_variant_test',
      'variant' => 'block_display',
      'label' => 'Metatag Variant',
      'page' => 'metatag_page_manager_test',
      'weight' => 10,
    ])->save();

    \Drupal::service("router.builder")->rebuild();

    // Log in as user 1.
    $this->loginUser1();
  }

  /**
   * Tests a single variant page.
   */
  public function testSingleVariantPage() {
    $this->drupalGet('/metatag-test');
    $this->assertSession->statusCodeEquals(200);

    // Confirm what the page title looks like by default.
    $this->assertSession->titleEquals('Metatag Page | Drupal');

    // Create the Metatag object through the UI to check the custom label.
    $edit = [
      'id' => 'page_variant__metatag_page_manager_variant_test',
      'title' => 'My title',
    ];

    $this->drupalPostForm('/admin/config/search/metatag/add', $edit, 'Save');
    $this->assertSession->pageTextContains('Page Variant: Metatag Page: Metatag Variant');

    // Clear caches to load the right metatags.
    drupal_flush_all_caches();

    $this->drupalGet('/metatag-test');
    $this->assertSession->statusCodeEquals(200);

    // Confirm what the page title is overridden.
    $this->assertSession->titleEquals('My title');
  }

  /**
   * Tests a multi-variant page.
   */
  public function testMultipleVariantPage() {
    // Make the old variant require an authenticated user.
    $old_variant = PageVariant::load('metatag_page_manager_variant_test');
    $selection = [
      'id' => 'user_role',
      'roles' => [
        'anonymous' => 'anonymous',
      ],
      'negate' => TRUE,
      'context_mapping' => [
        'user' => '@user.current_user_context:current_user',
      ],
    ];
    $old_variant->set('selection_criteria', [$selection]);
    $old_variant->save();

    // Add a new variant that only anonymous visitors can see.
    $new_variant = PageVariant::create([
      'id' => 'metatag_page_manager_multiple_variant_test',
      'variant' => 'block_display',
      'label' => 'Anonymous variant',
      'page' => 'metatag_page_manager_test',
      'weight' => 0,
    ]);
    $selection = [
      'id' => 'user_role',
      'roles' => [
        'anonymous' => 'anonymous',
      ],
      'negate' => FALSE,
      'context_mapping' => [
        'user' => '@user.current_user_context:current_user',
      ],
    ];
    $new_variant->set('selection_criteria', [$selection]);
    $new_variant->save();

    // Load the admin page and confirm the configuration.
    $this->drupalGet('admin/structure/page_manager/manage/metatag_page_manager_test/general');
    $this->assertSession->statusCodeEquals(200);

    // Clear caches to load the right meta tags.
    drupal_flush_all_caches();

    $this->drupalGet('/metatag-test');
    $this->assertSession->statusCodeEquals(200);

    // Confirm what the page title looks like by default.
    $this->assertSession->titleEquals('Metatag Page | Drupal');

    // Create the Metatag object through the UI to check the custom label.
    $edit = [
      'id' => 'page_variant__metatag_page_manager_variant_test',
      'title' => 'My title',
    ];

    $this->drupalPostForm('/admin/config/search/metatag/add', $edit, 'Save');
    $this->assertSession->pageTextContains('Page Variant: Metatag Page: Metatag Variant');

    // Clear caches to load the right metatags.
    drupal_flush_all_caches();

    $this->drupalGet('/metatag-test');
    $this->assertSession->statusCodeEquals(200);

    // Confirm what the page title is overridden.
    $this->assertSession->titleEquals('My title');

    // Visiting page as anon user, should get the default title.
    $this->drupalLogout();
    $this->drupalGet('/metatag-test');
    $this->assertSession->statusCodeEquals(200);

    // Confirm what the page title looks like by default.
    $this->assertSession->titleEquals('Metatag Page | Drupal');

    // Login and add custom metatag for anonymous user variant.
    $this->loginUser1();
    // Create the Metatag object through the UI to check the custom label.
    $edit = [
      'id' => 'page_variant__metatag_page_manager_multiple_variant_test',
      'title' => 'My title anonymous',
    ];

    $this->drupalPostForm('/admin/config/search/metatag/add', $edit, 'Save');
    // The first-weighted variant (Anonymous variant) will receive the Metatag
    // defaults.
    $this->assertSession->pageTextContains('Page Variant: Metatag Page: Anonymous variant');

    // Clear caches to load the right metatags.
    drupal_flush_all_caches();

    // Visit page as logged in user and confirm the right title.
    $this->drupalGet('/metatag-test');
    $this->assertSession->statusCodeEquals(200);
    $this->assertSession->titleEquals('My title');

    // Visit page as anonymous user and confirm the right title.
    $this->drupalLogout();
    $this->drupalGet('/metatag-test');
    $this->assertSession->statusCodeEquals(200);
    $this->assertSession->titleEquals('My title anonymous');
  }

}
