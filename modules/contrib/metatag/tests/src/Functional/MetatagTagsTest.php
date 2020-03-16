<?php

namespace Drupal\Tests\metatag\Functional;

/**
 * Tests that each of the Metatag base tags work correctly.
 *
 * @group metatag
 */
class MetatagTagsTest extends MetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  private $tags = [
    'abstract',
    'cache_control',
    'canonical_url',
    'content_language',
    'description',
    'expires',
    'generator',
    'geo_placename',
    'geo_position',
    'geo_region',
    'google',
    'icbm',
    'image_src',
    'keywords',
    'news_keywords',
    'next',
    'original_source',
    'pragma',
    'prev',
    'rating',
    'referrer',
    'refresh',
    'revisit_after',
    'rights',
    'robots',
    'set_cookie',
    'shortlink',
    'standout',
    'title',
  ];

  /**
   * Each of these meta tags has a different tag name vs its internal name.
   */
  private function getTestTagName($tag_name) {
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
    elseif ($tag_name == 'set_cookie') {
      $tag_name = 'set-cookie';
    }

    return $tag_name;
  }

  /**
   * Implements {tag_name}TestFieldXpath() for 'abstract'.
   */
  private function abstractTestFieldXpath() {
    return "//textarea[@name='abstract']";
  }

  /**
   * Implements {tag_name}TestNameAttribute() for 'author'.
   */
  private function authorTestOutputXpath() {
    return "//link[@rel='author']";
  }

  /**
   * Implements {tag_name}TestValueAttribute() for 'author'.
   */
  private function authorTestValueAttribute() {
    return 'href';
  }

  /**
   * Implements {tag_name}TestNameAttribute() for 'canonical_url'.
   */
  private function canonicalUrlTestOutputXpath() {
    return "//link[@rel='canonical']";
  }

  /**
   * Implements {tag_name}TestValueAttribute() for 'canonical_url'.
   */
  private function canonicalUrlTestValueAttribute() {
    return 'href';
  }

  /**
   * Implements {tag_name}TestNameAttribute() for 'content_language'.
   */
  private function contentLanguageTestNameAttribute() {
    return 'http-equiv';
  }

  /**
   * Implements {tag_name}TestNameAttribute() for 'set_cookie'.
   */
  private function setCookieTestNameAttribute() {
    return 'http-equiv';
  }

  /**
   * Implements {tag_name}TestFieldXpath() for 'description'.
   */
  private function descriptionTestFieldXpath() {
    return "//textarea[@name='description']";
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'image_src'.
   */
  private function imageSrcTestOutputXpath() {
    return "//link[@rel='image_src']";
  }

  /**
   * Implements {tag_name}TestValueAttribute() for 'image_src'.
   */
  private function imageSrcTestValueAttribute() {
    return 'href';
  }

  /**
   * Implements {tag_name}TestNameAttribute() for 'next'.
   */
  private function nextUrlTestOutputXpath() {
    return "//link[@rel='next']";
  }

  /**
   * Implements {tag_name}TestValueAttribute() for 'next'.
   */
  private function nextUrlTestValueAttribute() {
    return 'href';
  }

  /**
   * Implements {tag_name}TestNameAttribute() for 'prev'.
   */
  private function prevUrlTestOutputXpath() {
    return "//link[@rel='prev']";
  }

  /**
   * Implements {tag_name}TestValueAttribute() for 'prev'.
   */
  private function prevUrlTestValueAttribute() {
    return 'href';
  }

  /**
   * Implements {tag_name}TestFieldXpath() for 'referrer'.
   */
  private function referrerTestFieldXpath() {
    return "//select[@name='referrer']";
  }

  /**
   * Implements {tag_name}TestFieldXpath() for 'robots'.
   */
  private function robotsTestFieldXpath() {
    return "//input[@name='robots[index]' and @type='checkbox']";
  }

  /**
   * Implements {tag_name}TestValue() for 'referrer'.
   */
  private function referrerTestValue() {
    return 'origin';
  }

  /**
   * Implements {tag_name}TestValue() for 'robots'.
   */
  private function robotsTestKey() {
    return 'robots[index]';
  }

  /**
   * Implements {tag_name}TestValue() for 'robots'.
   */
  private function robotsTestValue() {
    return TRUE;
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'shortlink'.
   */
  private function shortlinkTestOutputXpath() {
    return "//link[@rel='shortlink']";
  }

  /**
   * Implements {tag_name}TestValueAttribute() for 'shortlink'.
   */
  private function shortlinkTestValueAttribute() {
    return 'href';
  }

}
