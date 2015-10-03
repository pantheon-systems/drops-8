<?php
/**
 * @file
 * Contains \Drupal\ckeditor\Tests\CKEditorToolbarButtonTest.
 */

namespace Drupal\ckeditor\Tests;


use Drupal\filter\Entity\FilterFormat;
use Drupal\editor\Entity\Editor;
use Drupal\simpletest\WebTestBase;
use Drupal\Component\Serialization\Json;

/**
 * Tests CKEditor toolbar buttons when the language direction is RTL.
 *
 * @group ckeditor
 */
class CKEditorToolbarButtonTest extends WebTestBase {

  /**
   * Modules to enable for this test.
   *
   * @var array
   */
  public static $modules = ['filter', 'editor', 'ckeditor', 'locale'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a text format and associate this with CKEditor.
    FilterFormat::create([
      'format' => 'full_html',
      'name' => 'Full HTML',
      'weight' => 1,
      'filters' => [],
    ])->save();
    Editor::create([
      'format' => 'full_html',
      'editor' => 'ckeditor',
    ])->save();

    // Create a new user with admin rights.
    $this->admin_user = $this->drupalCreateUser([
      'administer languages',
      'access administration pages',
      'administer site configuration',
      'administer filters',
    ]);
  }

  /**
   * Method tests CKEditor image buttons.
   */
  public function testImageButtonDisplay() {
    global $base_url;
    $this->drupalLogin($this->admin_user);

    // Install the Arabic language (which is RTL) and configure as the default.
    $edit = [];
    $edit['predefined_langcode'] = 'ar';
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));

    $edit = ['site_default_language' => 'ar'];
    $this->drupalPostForm('admin/config/regional/language', $edit, t('Save configuration'));
    // Once the default language is changed, go to the tested text format
    // configuration page.
    $this->drupalGet('admin/config/content/formats/manage/full_html');

    // Check if any image button is loaded in CKEditor json.
    $json_encode = function($html) {
      return trim(Json::encode($html), '"');
    };
    $markup = $json_encode($base_url . '/core/modules/ckeditor/js/plugins/drupalimage/image.png');
    $this->assertRaw($markup);
  }

}
