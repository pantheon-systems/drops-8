<?php

namespace Drupal\Tests\metatag\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verify that different meta tag API options are supported.
 *
 * @group metatag
 */
class MetatagTagTypesTest extends BrowserTestBase {

  /**
   * Profile to use.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * Admin user.
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
    // Needed for token handling.
    'token',

    // Needed for the field UI testing.
    'field_ui',

    // Needed for the basic entity testing.
    'entity_test',

    // Needed to verify that nothing is broken for unsupported entities.
    'contact',

    // The base module.
    'metatag',

    // Some extra custom logic for testing Metatag.
    'metatag_test_tag',

    // Needed for testSecureTagOption().
    'metatag_open_graph',
  ];

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'access administration pages',
    'view test entity',
    'administer entity_test fields',
    'administer entity_test content',
    'administer meta tags',
  ];

  /**
   * Sets the test up.
   */
  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser($this->permissions);
    $this->drupalLogin($this->adminUser);

    // Add a metatag field to the entity type test_entity.
    $this->drupalGet('entity_test/structure/entity_test/fields/add-field');
    $this->assertResponse(200);
    $edit = [
      'label' => 'Metatag',
      'field_name' => 'metatag',
      'new_storage_type' => 'metatag',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and continue'));
    $this->drupalPostForm(NULL, [], t('Save field settings'));
    $this->container->get('entity.manager')->clearCachedFieldDefinitions();
  }

  /**
   * Tests whether HTML is correctly removed from metatags.
   *
   * Tests three values in meta tags -- one without any HTML; one with raw html;
   * and one with escaped HTML. To pass all HTML including escaped should be
   * removed.
   */
  public function testHtmlIsRemoved() {
    $this->drupalGet('admin/config/search/metatag/global');
    $this->assertResponse(200);
    $values = [
      'abstract' => 'No HTML here',
      'description' => '<html><body><p class="test">Surrounded by raw HTML</p></body></html>',
      'keywords' => '&lt;html&gt;&lt;body&gt;&lt;p class="test"&gt;Surrounded by escaped HTML&lt;/p&gt;&lt;/body&gt;&lt;/html&gt;',
    ];

    $this->drupalPostForm(NULL, $values, 'Save');
    $this->assertText('Saved the Global Metatag defaults.');
    drupal_flush_all_caches();
    $this->drupalGet('hit-a-404');
    $this->assertResponse(404);

    $this->assertRaw('<meta name="abstract" content="No HTML here" />', t('Test with no HTML content'));
    $this->assertRaw('<meta name="description" content="Surrounded by raw HTML" />', t('Test with raw HTML content'));
    $this->assertRaw('<meta name="keywords" content="Surrounded by escaped HTML" />', t('Test with escaped HTML content'));
  }

  /**
   * Tests the 'secure' meta tag attribute.
   *
   * Tests insecure values in og:image:secure_url (a tag with secure attribute
   * set to TRUE) and in og:image (a tag with secure attribute set to FALSE). To
   * To pass og:image_secure should be changed to https:// and og:image
   * unchanged.
   */
  public function testSecureTagOption() {
    $this->drupalGet('admin/config/search/metatag/global');
    $this->assertResponse(200);
    $values = [
      'og_image' => 'http://blahblahblah.com/insecure.jpg',
      'og_image_secure_url' => 'http://blahblahblah.com/secure.jpg',
    ];
    $this->drupalPostForm(NULL, $values, 'Save');
    $this->assertText('Saved the Global Metatag defaults.');
    drupal_flush_all_caches();
    $this->drupalGet('');
    $this->assertResponse(200);

    $this->assertRaw('<meta property="og:image" content="http://blahblahblah.com/insecure.jpg" />', t('Test og:image with regular http:// link'));
    $this->assertRaw('<meta property="og:image:secure_url" content="https://blahblahblah.com/secure.jpg" />', t('Test og:image:secure_url updated regular http:// link to https://'));
  }

  /**
   * Check the contact form.
   *
   * @todo Move this somewhere else.
   */
  public function testContactForm() {
    // Test a route where the entity for that route does not implement
    // ContentEntityInterface.
    $controller = \Drupal::entityTypeManager()->getStorage('contact_form');
    $controller->create([
      'id' => 'test_contact_form',
    ])->save();
    $account = $this->drupalCreateUser(['access site-wide contact form']);
    $this->drupalLogin($account);
    $this->drupalGet('contact/test_contact_form');
    $this->assertResponse(200);
  }

  /**
   * Check URL handling.
   *
   * @todo Finish.
   */
  public function todoTestUrl() {
    // {@code}
    // $save_label = (floatval(\Drupal::VERSION) <= 8.3) ? t('Save and publish') : t('Save');
    // // Tests meta tags with URLs work.
    // $this->drupalGet($this->entity_add_path);
    // $this->assertResponse(200);
    // $edit = [
    //   'name[0][value]' => 'UrlTags',
    //   'user_id[0][target_id]' => 'foo (' . $this->adminUser->id() . ')',
    //   'field_metatag[0][advanced][original_source]' => 'http://example.com/foo.html',
    // ];
    // $this->drupalPostForm(NULL, $edit, $save_label);
    // $entities = entity_load_multiple_by_properties('entity_test', [
    //   'name' => 'UrlTags',
    // ]);
    // $this->assertEqual(1, count($entities), 'Entity was saved');
    // $entity = reset($entities);
    // $this->drupalGet($this->entity_base_path . '/' . $entity->id());
    // $this->assertResponse(200);
    // $elements = $this->cssSelect("meta[name='original-source']");
    // $this->assertTrue(count($elements) === 1, 'Found original source metatag from defaults');
    // $this->assertEqual((string) $elements[0]['content'], $edit['field_metatag[0][advanced][original_source]']);
    // {@endcode}
  }

}
