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
  private $tags = [
    'google_plus_author',
    'google_plus_description',
    'google_plus_image',
    'google_plus_name',
    'google_plus_publisher',
  ];

  /**
   * {@inheritdoc}
   */
  private $testNameAttribute = 'itemprop';

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
  private function getTestTagName($tag_name) {
    $tag_name = str_replace('google_plus_', 'itemprop:', $tag_name);
    if ($tag_name == 'itemprop:publisher') {
      $tag_name = 'publisher';
    }
    return $tag_name;
  }

  /**
   * Implements {tag_name}TestNameAttribute() for 'author'.
   */
  private function googlePlusAuthorTestOutputXpath() {
    return "//link[@rel='author']";
  }

  /**
   * Implements {tag_name}TestValueAttribute() for 'author'.
   */
  private function googlePlusAuthorTestValueAttribute() {
    return 'href';
  }

  /**
   * Implements {tag_name}TestNameAttribute() for 'publisher'.
   */
  private function googlePlusPublisherTestOutputXpath() {
    return "//link[@rel='publisher']";
  }

  /**
   * Implements {tag_name}TestValueAttribute() for 'publisher'.
   */
  private function googlePlusPublisherTestValueAttribute() {
    return 'href';
  }

}
