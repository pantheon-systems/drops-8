<?php

namespace Drupal\Tests\metatag\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verify that the configured defaults load as intended.
 *
 * @group metatag
 */
class DefaultTags extends BrowserTestBase {

  // Contains helper methods.
  use MetatagHelperTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    // Modules for core functionality.
    'node',
    'taxonomy',
    'user',

    // Need this so that the /node page exists.
    'views',

    // Contrib dependencies.
    'token',

    // This module.
    'metatag',

    // Use the custom route to verify the site works.
    'metatag_test_custom_route',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Set the front page to the main /node page, so that the front page is not
    // just the login page.
    \Drupal::configFactory()
      ->getEditable('system.site')
      ->set('page.front', '/node')
      ->save(TRUE);
  }

  /**
   * Test the default values for the front page.
   */
  public function testFrontpage() {
    $this->drupalGet('<front>');
    $this->assertResponse(200);
    $xpath = $this->xpath("//link[@rel='canonical']");
    $this_page_url = $this->buildUrl('<front>');
    $this->assertEqual((string) $xpath[0]->getAttribute('href'), $this_page_url);
  }

  /**
   * Test the default values for a custom route.
   */
  public function testCustomRoute() {
    $this->drupalGet('metatag_test_custom_route');
    $this->assertResponse(200);
    $this->assertText('Hello world!');

    // Check the meta tags.
    $xpath = $this->xpath("//link[@rel='canonical']");
    $this_page_url = $this->buildUrl('/metatag_test_custom_route');
    $this->assertEqual((string) $xpath[0]->getAttribute('href'), $this_page_url);
  }

  /**
   * Test the default values for a Node entity.
   */
  public function testNode() {
    $node = $this->createContentTypeNode();
    $this_page_url = $node->toUrl('canonical', ['absolute' => TRUE])->toString();

    // Load the node's entity page.
    $this->drupalGet($this_page_url);
    $this->assertResponse(200);

    // Check the meta tags.
    $xpath = $this->xpath("//link[@rel='canonical']");
    $this->assertEqual((string) $xpath[0]->getAttribute('href'), $this_page_url);
  }

  /**
   * Test the default values for a Term entity.
   */
  public function testTerm() {
    $vocab = $this->createVocabulary();
    $term = $this->createTerm(['vid' => $vocab->id()]);
    $this_page_url = $term->toUrl('canonical', ['absolute' => TRUE])->toString();
    $this->drupalGet($this_page_url);
    $this->assertResponse(200);

    // Check the meta tags.
    $xpath = $this->xpath("//link[@rel='canonical']");
    $this->assertEqual((string) $xpath[0]->getAttribute('href'), $this_page_url);
  }

  /**
   * Test the default values for a User entity.
   */
  public function testUser() {
    $this->loginUser1();
    $account = \Drupal::currentUser()->getAccount();
    $this_page_url = $account->toUrl('canonical', ['absolute' => TRUE])->toString();

    // Load the user's entity page.
    $this->drupalGet($this_page_url);
    $this->assertResponse(200);

    // Check the meta tags.
    $xpath = $this->xpath("//link[@rel='canonical']");
    $this->assertEqual((string) $xpath[0]->getAttribute('href'), $this_page_url);
    $this->drupalLogout();
  }

  /**
   * Test the default values for the user login page, etc.
   */
  public function testUserLoginPages() {
    $front_url = $this->buildUrl('<front>', ['absolute' => TRUE]);;

    // A list of paths to examine.
    $routes = [
      '/user/login',
      '/user/register',
      '/user/password',
    ];

    foreach ($routes as $route) {
      // Identify the path to load.
      $this_page_url = $this->buildUrl($route, ['absolute' => TRUE]);
      $this->assertTrue(!empty($this_page_url));

      // Load the path.
      $this->drupalGet($this_page_url);
      $this->assertResponse(200);

      // Check the meta tags.
      $xpath = $this->xpath("//link[@rel='canonical']");
      $this->assertNotEqual((string) $xpath[0]->getAttribute('href'), $front_url);
      $this->assertEqual((string) $xpath[0]->getAttribute('href'), $this_page_url);
    }
  }

}
