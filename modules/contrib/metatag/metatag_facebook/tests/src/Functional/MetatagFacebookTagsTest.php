<?php

namespace Drupal\Tests\metatag_facebook\Functional;

use Drupal\Tests\metatag\Functional\MetatagTagsTestBase;

/**
 * Tests that each of the Metatag Facebook tags work correctly.
 *
 * @group metatag
 */
class MetatagFacebookTagsTest extends MetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected $tags = [
    'fb_admins',
    'fb_app_id',
    'fb_pages',
  ];

  /**
   * {@inheritdoc}
   */
  protected $testNameAttribute = 'property';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::$modules[] = 'metatag_facebook';
    parent::setUp();
  }

  /**
   * Each of these meta tags has a different tag name vs its internal name.
   */
  protected function getTestTagName($tag_name) {
    $tag_name = str_replace('fb_', 'fb:', $tag_name);
    return $tag_name;
  }

}
