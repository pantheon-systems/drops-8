<?php

namespace Drupal\metatag_google_plus\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\metatag\Tests\MetatagTagsTestBase;

/**
 * Tests that each of the Metatag Google Plus tags work correctly.
 *
 * @group metatag
 */
class MetatagGooglePlusTagsTest extends MetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  public $tags = [
    'google_plus_author',
    'google_plus_description',
    'google_plus_image',
    'google_plus_name',
    'google_plus_publisher',
  ];

  /**
   * The attribute to look for to indicate which tag.
   */
  public $test_name_attribute = 'itemprop';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::$modules[] = 'metatag_google_plus';
    parent::setUp();
  }

  /**
   * Each of these meta tags has a different tag name vs its internal name.
   */
  public function getTestTagName($tag_name) {
    $tag_name = str_replace('google_plus_', 'itemprop:', $tag_name);
    if ($tag_name == 'itemprop:publisher') {
      $tag_name = 'publisher';
    }
    return $tag_name;
  }

  /**
   * Implements {meta_tag_name}_test_name_attribute() for 'author'.
   */
  public function google_plus_author_test_output_xpath() {
    return "//link[@rel='author']";
  }

  /**
   * Implements {meta_tag_name}_test_value_attribute() for 'author'.
   */
  public function google_plus_author_test_value_attribute() {
    return 'href';
  }

  /**
   * Implements {meta_tag_name}_test_name_attribute() for 'publisher'.
   */
  public function google_plus_publisher_test_output_xpath() {
    return "//link[@rel='publisher']";
  }

  /**
   * Implements {meta_tag_name}_test_value_attribute() for 'publisher'.
   */
  public function google_plus_publisher_test_value_attribute() {
    return 'href';
  }

}
