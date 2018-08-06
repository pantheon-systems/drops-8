<?php

namespace Drupal\metatag_hreflang\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\metatag\Tests\MetatagTagsTestBase;

/**
 * Tests that each of the Metatag hreflang tags work correctly.
 *
 * @group metatag
 */
class MetatagHreflangTagsTest extends MetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  public $tags = [
    'hreflang_xdefault',
  ];

  /**
   * {@inheritdoc}
   */
  public $test_tag = 'link';

  /**
   * {@inheritdoc}
   */
  public $test_name_attribute = 'hreflang';

  /**
   * {@inheritdoc}
   */
  public $test_value_attribute = 'href';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::$modules[] = 'metatag_hreflang';
    parent::setUp();
  }

  /**
   * Each of these meta tags has a different tag name vs its internal name.
   */
  public function getTestTagName($tag_name) {
    return str_replace('hreflang_', '', $tag_name);
  }

  /**
   * Implements {meta_tag_name}_test_output_xpath() for 'hreflang_xdefault'.
   */
  public function hreflang_xdefault_test_output_xpath() {
    return "//link[@hreflang='x-default']";
  }

}
