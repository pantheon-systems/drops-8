<?php

namespace Drupal\Tests\metatag_pinterest\Functional;

use Drupal\Tests\metatag\Functional\MetatagTagsTestBase;

/**
 * Tests that each of the Metatag Pinterest tags work correctly.
 *
 * @group metatag
 */
class MetatagPinterestTagsTest extends MetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  private $tags = [
    'pinterest_description',
    'pinterest_id',
    'pinterest_media',
    'pinterest_nopin',
    'pinterest_nohover',
    'pinterest_nosearch',
    'pinterest_url',
  ];

  /**
   * {@inheritdoc}
   */
  private $testTag = 'meta';

  /**
   * {@inheritdoc}
   */
  private $testNameAttribute = 'property';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::$modules[] = 'metatag_pinterest';
    parent::setUp();
  }

  /**
   * Each of these meta tags has a different tag name vs its internal name.
   */
  private function getTestTagName($tag_name) {
    if ($tag_name == ('pinterest_nopin' || 'pinterest_nohover' || 'pinterest_nosearch')) {
      $tag_name = 'pinterest';
    }
    else {
      // Replace "pinterest_" with "pin:".
      $tag_name = str_replace('pinterest_', 'pin:', $tag_name);
    }

    return $tag_name;
  }

  /**
   * Implements {tag_name}TestFieldXpath() for 'pinterest'.
   */
  private function pinterestTestFieldXpath() {
    return "//input[@name='pinterest[index]' and @type='checkbox']";
  }

  /**
   * Implements {tag_name}TestKey() for 'pinterest'.
   */
  private function pinterestTestKey() {
    return 'pinterest[index]';
  }

  /**
   * Implements {tag_name}TestValue() for 'pinterest'.
   */
  private function pinterestTestValue() {
    return TRUE;
  }

}
