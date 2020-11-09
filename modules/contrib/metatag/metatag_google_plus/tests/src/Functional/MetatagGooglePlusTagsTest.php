<?php

namespace Drupal\Tests\metatag_google_plus\Functional;

use Drupal\Tests\metatag\Functional\MetatagTagsTestBase;

/**
 * Tests that each of the Metatag Google Plus tags work correctly.
 *
 * @group metatag
 */
class MetatagGooglePlusTagsTest extends MetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected $tags = [
    'google_plus_author',
    'google_plus_description',
    'google_plus_image',
    'google_plus_name',
    'google_plus_publisher',
  ];

  /**
   * {@inheritdoc}
   */
  protected $testNameAttribute = 'itemprop';

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
  protected function getTestTagName($tag_name) {
    return str_replace('google_plus_', '', $tag_name);
  }

  /**
   * Implements {tag_name}TestNameAttribute() for 'author'.
   */
  protected function googlePlusAuthorTestOutputXpath() {
    return "//link[@rel='author']";
  }

  /**
   * Implements {tag_name}TestValueAttribute() for 'author'.
   */
  protected function googlePlusAuthorTestValueAttribute() {
    return 'href';
  }

  /**
   * Implements {tag_name}TestNameAttribute() for 'publisher'.
   */
  protected function googlePlusPublisherTestOutputXpath() {
    return "//link[@rel='publisher']";
  }

  /**
   * Implements {tag_name}TestValueAttribute() for 'publisher'.
   */
  protected function googlePlusPublisherTestValueAttribute() {
    return 'href';
  }

}
