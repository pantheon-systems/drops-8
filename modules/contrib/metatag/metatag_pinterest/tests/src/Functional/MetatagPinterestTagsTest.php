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
  protected $tags = [
    'pinterest_description',
    'pinterest_id',
    'pinterest_media',
    'pinterest_url',
    // @todo Fix these.
    // 'pinterest_nopin',
    // 'pinterest_nohover',
    // 'pinterest_nosearch',
  ];

  /**
   * {@inheritdoc}
   */
  protected $testTag = 'meta';

  /**
   * {@inheritdoc}
   */
  protected $testNameAttribute = 'property';

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
  protected function getTestTagName($tag_name) {
    if ($tag_name == 'pinterest_nopin' || $tag_name == 'pinterest_nohover' || $tag_name == 'pinterest_nosearch') {
      $tag_name = 'pinterest';
    }
    else {
      // Replace "pinterest_" with "pin:".
      $tag_name = str_replace('pinterest_', 'pin:', $tag_name);
    }

    return $tag_name;
  }

  /**
   * Implements {tag_name}TestFieldXpath() for 'pinterest_nopin'.
   */
  protected function pinterestNopinTestFieldXpath() {
    return "//input[@name='pinterest_nopin' and @type='checkbox']";
  }

  /**
   * Implements {tag_name}TestFieldXpath() for 'pinterest_nohover'.
   */
  protected function pinterestNohoverTestFieldXpath() {
    return "//input[@name='pinterest_nohover' and @type='checkbox']";
  }

  /**
   * Implements {tag_name}TestFieldXpath() for 'pinterest_nosearch'.
   */
  protected function pinterestNosearchTestFieldXpath() {
    return "//input[@name='pinterest_nosearch' and @type='checkbox']";
  }

}
