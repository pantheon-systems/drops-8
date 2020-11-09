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
  protected $tags = [
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
    // @todo The title tag needs to be handled differently.
    // 'title',
  ];

  /**
   * Each of these meta tags has a different tag name vs its internal name.
   */
  protected function getTestTagName($tag_name) {
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
    elseif ($tag_name == 'cache_control') {
      $tag_name = 'cache-control';
    }
    elseif ($tag_name == 'revisit_after') {
      $tag_name = 'revisit-after';
    }

    return $tag_name;
  }

  /**
   * Implements {tag_name}TestFieldXpath() for 'abstract'.
   */
  protected function abstractTestFieldXpath() {
    return "//textarea[@name='abstract']";
  }

  /**
   * Implements {tag_name}TestNameAttribute() for 'author'.
   */
  protected function authorTestOutputXpath() {
    return "//link[@rel='author']";
  }

  /**
   * Implements {tag_name}TestValueAttribute() for 'author'.
   */
  protected function authorTestValueAttribute() {
    return 'href';
  }

  /**
   * Implements {tag_name}TestNameAttribute() for 'canonical_url'.
   */
  protected function canonicalUrlTestOutputXpath() {
    return "//link[@rel='canonical']";
  }

  /**
   * Implements {tag_name}TestValueAttribute() for 'canonical_url'.
   */
  protected function canonicalUrlTestValueAttribute() {
    return 'href';
  }

  /**
   * Implements {tag_name}TestNameAttribute() for 'content_language'.
   */
  protected function contentLanguageTestNameAttribute() {
    return 'http-equiv';
  }

  /**
   * Implements {tag_name}TestNameAttribute() for 'set_cookie'.
   */
  protected function setCookieTestNameAttribute() {
    return 'http-equiv';
  }

  /**
   * Implements {tag_name}TestFieldXpath() for 'description'.
   */
  protected function descriptionTestFieldXpath() {
    return "//textarea[@name='description']";
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'image_src'.
   */
  protected function imageSrcTestOutputXpath() {
    return "//link[@rel='image_src']";
  }

  /**
   * Implements {tag_name}TestValueAttribute() for 'image_src'.
   */
  protected function imageSrcTestValueAttribute() {
    return 'href';
  }

  /**
   * Implements {tag_name}TestNameAttribute() for 'next'.
   */
  protected function nextTestOutputXpath() {
    return "//link[@rel='next']";
  }

  /**
   * Implements {tag_name}TestValueAttribute() for 'next'.
   */
  protected function nextTestValueAttribute() {
    return 'href';
  }

  /**
   * Implements {tag_name}TestNameAttribute() for 'prev'.
   */
  protected function prevTestOutputXpath() {
    return "//link[@rel='prev']";
  }

  /**
   * Implements {tag_name}TestValueAttribute() for 'prev'.
   */
  protected function prevTestValueAttribute() {
    return 'href';
  }

  /**
   * Implements {tag_name}TestFieldXpath() for 'referrer'.
   */
  protected function referrerTestFieldXpath() {
    return "//select[@name='referrer']";
  }

  /**
   * Implements {tag_name}TestValue() for 'referrer'.
   */
  protected function referrerTestValue() {
    return 'origin';
  }

  /**
   * Implements {tag_name}TestNameAttribute() for 'refresh'.
   */
  protected function refreshTestNameAttribute() {
    return 'http-equiv';
  }

  /**
   * Implements {tag_name}TestFieldXpath() for 'robots'.
   */
  protected function robotsTestFieldXpath() {
    return "//input[@name='robots[index]' and @type='checkbox']";
  }

  /**
   * Implements {tag_name}TestValue() for 'robots'.
   */
  protected function robotsTestKey() {
    return 'robots[index]';
  }

  /**
   * Implements {tag_name}TestValue() for 'robots'.
   */
  protected function robotsTestValue() {
    return 'index';
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'shortlink'.
   */
  protected function shortlinkTestOutputXpath() {
    return "//link[@rel='shortlink']";
  }

  /**
   * Implements {tag_name}TestValueAttribute() for 'shortlink'.
   */
  protected function shortlinkTestValueAttribute() {
    return 'href';
  }

}
