<?php

namespace Drupal\metatag_app_links\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\metatag\Tests\MetatagTagsTestBase;

/**
 * Tests that each of the App Links tags work correctly.
 *
 * @group metatag
 */
class MetatagAppLinksTagsTest extends MetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  public $tags = [
    'al_android_app_name',
    'al_android_class',
    'al_android_package',
    'al_android_url',
    'al_ios_app_name',
    'al_ios_app_store_id',
    'al_ios_url',
    'al_ipad_app_name',
    'al_ipad_app_store_id',
    'al_ipad_url',
    'al_iphone_app_name',
    'al_iphone_app_store_id',
    'al_iphone_url',
    'al_web_should_fallback',
    'al_web_url',
    'al_windows_app_id',
    'al_windows_app_name',
    'al_windows_phone_app_id',
    'al_windows_phone_app_name',
    'al_windows_phone_url',
    'al_windows_universal_app_id',
    'al_windows_universal_app_name',
    'al_windows_universal_url',
    'al_windows_url',
  ];

  /**
   * {@inheritdoc}
   */
  public $test_name_attribute = 'property';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::$modules[] = 'metatag_app_links';
    parent::setUp();
  }

  /**
   * Each of these meta tags has a different tag name vs its internal name.
   */
  public function getTestTagName($tag_name) {
    $tag_name = str_replace('al_android_', 'al:android:', $tag_name);
    $tag_name = str_replace('al_ios_', 'al:ios:', $tag_name);
    $tag_name = str_replace('al_ipad_', 'al:ipad:', $tag_name);
    $tag_name = str_replace('al_iphone_', 'al:iphone:', $tag_name);
    $tag_name = str_replace('al_web_', 'al:web:', $tag_name);
    // Run the Windows subtype replacements first so that the generic Windows
    // one can still work.
    $tag_name = str_replace('al_windows_phone_', 'al:windows_phone:', $tag_name);
    $tag_name = str_replace('al_windows_universal_', 'al:windows_universal:', $tag_name);
    $tag_name = str_replace('al_windows_', 'al:windows:', $tag_name);
    return $tag_name;
  }

}
