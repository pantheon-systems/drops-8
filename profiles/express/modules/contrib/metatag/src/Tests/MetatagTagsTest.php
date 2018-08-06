<?php

namespace Drupal\metatag\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\metatag\Tests\MetatagTagsTestBase;

/**
 * Tests that each of the Metatag base tags work correctly.
 *
 * @group metatag
 */
class MetatagTagsTest extends MetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  public $tags = [
    'abstract',
    'canonical_url',
    'content_language',
    'description',
    'generator',
    'geo_placename',
    'geo_position',
    'geo_region',
    'icbm',
    'image_src',
    'keywords',
    'news_keywords',
    'original_source',
    'referrer',
    'rights',
    'robots',
    'shortlink',
    'standout',
    'title',
  ];

  /**
   * Each of these meta tags has a different tag name vs its internal name.
   */
  public function getTestTagName($tag_name) {
    if ($tag_name == 'geo_placename') {
      $tag_name = 'geo.placename';
    }
    elseif ($tag_name == 'geo_position') {
      $tag_name = 'geo.position';
    }
    elseif ($tag_name == 'geo_region') {
      $tag_name = 'geo.region';
    }
    elseif ($tag_name == 'content_language') {
      $tag_name = 'content-language';
    }
    elseif ($tag_name == 'original_source') {
      $tag_name = 'original-source';
    }
    return $tag_name;
  }

  /**
   * Implements {meta_tag_name}_test_field_xpath() for 'abstract'.
   */
  public function abstract_test_field_xpath() {
    return "//textarea[@name='abstract']";
  }

  /**
   * Implements {meta_tag_name}_test_name_attribute() for 'author'.
   */
  public function author_test_output_xpath() {
    return "//link[@rel='author']";
  }

  /**
   * Implements {meta_tag_name}_test_value_attribute() for 'author'.
   */
  public function author_test_value_attribute() {
    return 'href';
  }

  /**
   * Implements {meta_tag_name}_test_name_attribute() for 'canonical_url'.
   */
  public function canonical_url_test_output_xpath() {
    return "//link[@rel='canonical']";
  }

  /**
   * Implements {meta_tag_name}_test_value_attribute() for 'canonical_url'.
   */
  public function canonical_url_test_value_attribute() {
    return 'href';
  }

  /**
   * Implements {meta_tag_name}_test_name_attribute() for 'content_language'.
   */
  public function content_language_test_name_attribute() {
    return 'http-equiv';
  }

  /**
   * Implements {meta_tag_name}_test_field_xpath() for 'description'.
   */
  public function description_test_field_xpath() {
    return "//textarea[@name='description']";
  }

  /**
   * Implements {meta_tag_name}_test_output_xpath() for 'image_src'.
   */
  public function image_src_test_output_xpath() {
    return "//link[@rel='image_src']";
  }

  /**
   * Implements {meta_tag_name}_test_value_attribute() for 'image_src'.
   */
  public function image_src_test_value_attribute() {
    return 'href';
  }

  /**
   * Implements {meta_tag_name}_test_field_xpath() for 'referrer'.
   */
  public function referrer_test_field_xpath() {
    return "//select[@name='referrer']";
  }

  /**
   * Implements {meta_tag_name}_test_field_xpath() for 'robots'.
   */
  public function robots_test_field_xpath() {
    return "//input[@name='robots[index]' and @type='checkbox']";
  }

  /**
   * Implements {meta_tag_name}_test_value() for 'referrer'.
   */
  public function referrer_test_value() {
    return 'origin';
  }

  /**
   * Implements {meta_tag_name}_test_value() for 'robots'.
   */
  public function robots_test_key() {
    return 'robots[index]';
  }

  /**
   * Implements {meta_tag_name}_test_value() for 'robots'.
   */
  public function robots_test_value() {
    return TRUE;
  }

  /**
   * Implements {meta_tag_name}_test_output_xpath() for 'shortlink'.
   */
  public function shortlink_test_output_xpath() {
    return "//link[@rel='shortlink']";
  }

  /**
   * Implements {meta_tag_name}_test_value_attribute() for 'shortlink'.
   */
  public function shortlink_test_value_attribute() {
    return 'href';
  }

}
