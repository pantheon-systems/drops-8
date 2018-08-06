<?php

namespace Drupal\metatag\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Ensures that the Metatag field works correctly.
 *
 * @group Metatag
 */
class MetatagStringTest extends WebTestBase {

  /**
   * Admin user
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'token',
    'node',
    'field_ui',
    'metatag',
  ];

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'administer node fields',
    'administer content types',
    'access administration pages',
    'administer meta tags',
    'administer nodes',
    'bypass node access',
    'administer meta tags',
    'administer site configuration',
    'access content',
  ];

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser($this->permissions);
    $this->drupalLogin($this->adminUser);

    $this->drupalCreateContentType(['type' => 'page', 'display_submitted' => FALSE]);

    // Add a Metatag field to the content type.
    $this->drupalGet('admin/structure/types');
    $this->assertResponse(200);
    $this->drupalGet('admin/structure/types/manage/page/fields/add-field');
    $this->assertResponse(200);
    $edit = [
      'label' => 'Metatag',
      'field_name' => 'metatag_field',
      'new_storage_type' => 'metatag',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and continue'));
    $this->drupalPostForm(NULL, [], t('Save field settings'));
    $this->container->get('entity.manager')->clearCachedFieldDefinitions();
  }

  /**
   * Tests that a meta tag with single quote is not double escaped.
   */
  function testSingleQuote() {
    $this->_testAString("bla'bleblu");
  }

  /**
   * Tests that a meta tag with a double quote is not double escaped.
   */
  function testDoubleQuote() {
    $this->_testAString('bla"bleblu');
  }

  /**
   * Tests that a meta tag with an ampersand is not double escaped.
   */
  function testAmpersand() {
    $this->_testAString("blable&blu");
  }

  /**
   * Tests that specific strings are not double escaped.
   */
  function _testAString($string) {
    $this->_testConfig($string);
    $this->_testNode($string);
    $this->_testEncodedField($string);
  }

  /**
   * Tests that a specific config string is not double encoded.
   */
  function _testConfig($string) {
    // The original strings.
    $title_original = 'Title: ' . $string;
    $desc_original = 'Description: ' . $string;

    // The strings after they're encoded, but quotes will not be encoded.
    $title_encoded = htmlentities($title_original, ENT_QUOTES);
    $desc_encoded = htmlentities($desc_original, ENT_QUOTES);

    // The strings double-encoded, to make sure the tags aren't broken.
    $title_encodeded = htmlentities($title_encoded, ENT_QUOTES);
    $desc_encodeded = htmlentities($desc_encoded, ENT_QUOTES);

    // Update the Global defaults and test them.
    $this->drupalGet('admin/config/search/metatag/front');
    $this->assertResponse(200);
    $edit = [
      'title' => $title_original,
      'description' => $desc_original,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertResponse(200);

    $metatag_defaults = \Drupal::config('metatag.metatag_defaults.front');
    $default_title = $metatag_defaults->get('tags')['title'];
    $default_description = $metatag_defaults->get('tags')['description'];

    // Make sure the title tag is stored correctly.
    $this->assertEqual($title_original, $default_title, 'The title tag was stored in its original format.');
    $this->assertNotEqual($title_encoded, $default_title, 'The title tag was not stored in an encoded format.');
    $this->assertNotEqual($title_encodeded, $default_title, 'The title tag was not stored in a double-encoded format.');

    // Make sure the description tag is stored correctly.
    $this->assertEqual($desc_original, $default_description, 'The description tag was stored in its original format.');
    $this->assertNotEqual($desc_encoded, $default_description, 'The description tag was not stored in an encoded format.');
    $this->assertNotEqual($desc_encodeded, $default_description, 'The description tag was not stored in a double-encoded format.');

    // Set up a node without explicit metatag description. This causes the
    // global default to be used, which contains a token (node:summary). The
    // token value should be correctly translated.

    // Create a node.
    $this->drupalGet('node/add/page');
    $this->assertResponse(200);
    $edit = [
      'title[0][value]' => $title_original,
      'body[0][value]' => $desc_original,
    ];
    $save_label = (floatval(\Drupal::VERSION) <= 8.3) ? t('Save and publish') : t('Save');
    $this->drupalPostForm(NULL, $edit, $save_label);

    $this->config('system.site')->set('page.front', '/node/1')->save();

    // Load the front page.
    $this->drupalGet('<front>');
    $this->assertResponse(200);

    // Again, with xpath the HTML entities will be parsed automagically.
    $xpath_title = (string) current($this->xpath("//title"));
    $this->assertEqual($xpath_title, $title_original);
    $this->assertNotEqual($xpath_title, $title_encoded);
    $this->assertNotEqual($xpath_title, $title_encodeded);

    // The page title should be HTML encoded; have to do this check manually
    // because assertRaw() checks the raw HTML, not the parsed strings like
    // xpath does.
    $this->assertRaw('<title>' . $title_encoded . '</title>', 'Confirmed the node title tag is available in its encoded format.');
    $this->assertNoRaw('<title>' . $title_original . '</title>', 'Confirmed the node title tag is not available in its original format.');
    $this->assertNoRaw('<title>' . $title_encodeded . '</title>', 'Confirmed the node title tag is not double-double-encoded?');

    // Again, with xpath the HTML entities will be parsed automagically.
    $xpath = $this->xpath("//meta[@name='description']");
    $this->assertEqual($xpath[0]['content'], $desc_original);
    $this->assertNotEqual($xpath[0]['content'], $desc_encoded);
    $this->assertNotEqual($xpath[0]['content'], $desc_encodeded);
  }

  /**
   * Tests that a specific node string is not double escaped.
   */
  function _testNode($string) {
    $save_label = (floatval(\Drupal::VERSION) <= 8.3) ? t('Save and publish') : t('Save');

    // The original strings.
    $title_original = 'Title: ' . $string;
    $desc_original = 'Description: ' . $string;

    // The strings after they're encoded, but quotes will not be encoded.
    $title_encoded = htmlentities($title_original, ENT_QUOTES);
    $desc_encoded = htmlentities($desc_original, ENT_QUOTES);

    // The strings double-encoded, to make sure the tags aren't broken.
    $title_encodeded = htmlentities($title_encoded, ENT_QUOTES);
    $desc_encodeded = htmlentities($desc_encoded, ENT_QUOTES);

    // Update the Global defaults and test them.
    $this->drupalGet('admin/config/search/metatag/global');
    $this->assertResponse(200);
    $edit = [
      'title' => $title_original,
      'description' => $desc_original,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);

    // Set up a node without explicit metatag description. This causes the
    // global default to be used, which contains a token (node:summary). The
    // token value should be correctly translated.

    // Create a node.
    $this->drupalGet('node/add/page');
    $this->assertResponse(200);
    $edit = [
      'title[0][value]' => $title_original,
      'body[0][value]' => $desc_original,
    ];
    $this->drupalPostForm(NULL, $edit, $save_label);
    $this->assertResponse(200);

    // Load the node page.
    $this->drupalGet('node/1');
    $this->assertResponse(200);

    // Again, with xpath the HTML entities will be parsed automagically.
    $xpath_title = (string) current($this->xpath("//title"));
    $this->assertEqual($xpath_title, $title_original);
    $this->assertNotEqual($xpath_title, $title_encoded);
    $this->assertNotEqual($xpath_title, $title_encodeded);

    // The page title should be HTML encoded; have to do this check manually
    // because assertRaw() checks the raw HTML, not the parsed strings like
    // xpath does.
    $this->assertRaw('<title>' . $title_encoded . '</title>', 'Confirmed the node title tag is encoded.');
    // Again, with xpath the HTML entities will be parsed automagically.
    $xpath = $this->xpath("//meta[@name='description']");
    $value = (string) $xpath[0]['content'];
    $this->assertEqual($value, $desc_original);
    $this->assertNotEqual($value, $desc_encoded);
    $this->assertNotEqual($value, $desc_encodeded);

    // Normal meta tags should be encoded properly.
    $this->assertRaw('"' . $desc_encoded . '"', 'Confirmed the node "description" meta tag string was encoded properly.');
    // Normal meta tags with HTML entities should be displayed in their original
    // format.
    $this->assertNoRaw('"' . $desc_original . '"', 'Confirmed the node "description" meta tag string does not show in its original form.');
    // Normal meta tags should not be double-encoded.
    $this->assertNoRaw('"' . $desc_encodeded . '"', 'Confirmed the node "description" meta tag string was not double-encoded.');
  }

  /**
   * Tests that fields with encoded HTML entities will not be double-encoded.
   */
  function _testEncodedField($string) {
    $save_label = (floatval(\Drupal::VERSION) <= 8.3) ? t('Save and publish') : t('Save');

    // The original strings.
    $title_original = 'Title: ' . $string;
    $desc_original = 'Description: ' . $string;

    // The strings after they're encoded, but quotes will not be encoded.
    $desc_encoded = htmlentities($desc_original, ENT_QUOTES);

    // The strings double-encoded, to make sure the tags aren't broken.
    $desc_encodeded = htmlentities($desc_encoded, ENT_QUOTES);

    // Update the Global defaults and test them.
    $this->drupalGet('admin/config/search/metatag/global');
    $this->assertResponse(200);
    $edit = [
      'title' => $title_original,
      'description' => $desc_original,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);

    // Set up a node without explicit metatag description. This causes the
    // global default to be used, which contains a token (node:summary). The
    // token value should be correctly translated.

    // Create a node.
    $this->drupalGet('node/add/page');
    $this->assertResponse(200);
    $edit = [
      'title[0][value]' => $title_original,
      'body[0][value]' => $desc_original,
    ];
    $this->drupalPostForm(NULL, $edit, $save_label);
    $this->assertResponse(200);

    // Load the node page.
    $this->drupalGet('node/1');
    $this->assertResponse(200);

    // With xpath the HTML entities will be parsed automagically.
    $xpath = $this->xpath("//meta[@name='description']");
    $value = (string) $xpath[0]['content'];
    $this->assertEqual($value, $desc_original);
    $this->assertNotEqual($value, $desc_encoded);
    $this->assertNotEqual($value, $desc_encodeded);

    // Normal meta tags should be encoded properly.
    $this->assertRaw('"' . $desc_encoded . '"', 'Confirmed the node "description" meta tag string was encoded properly.');

    // Normal meta tags with HTML entities should be displayed in their original
    // format.
    $this->assertNoRaw('"' . $desc_original . '"', 'Confirmed the node "description" meta tag string does not show in its original form.');

    // Normal meta tags should not be double-encoded.
    $this->assertNoRaw('"' . $desc_encodeded . '"', 'Confirmed the node "description" meta tag string was not double-encoded.');
  }

}

