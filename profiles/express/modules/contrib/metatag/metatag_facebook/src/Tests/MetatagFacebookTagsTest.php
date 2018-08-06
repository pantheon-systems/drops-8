<?php

namespace Drupal\metatag_facebook\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\metatag\Tests\MetatagTagsTestBase;

/**
 * Tests that each of the Metatag Facebook tags work correctly.
 *
 * @group metatag
 */
class MetatagFacebookTagsTest extends MetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  public $tags = [
    'fb_admins',
    'fb_app_id',
    'fb_pages',
  ];

  /**
   * The attribute to look for to indicate which tag.
   */
  public $test_name_attribute = 'property';

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
  public function getTestTagName($tag_name) {
    $tag_name = str_replace('fb_', 'fb:', $tag_name);
    return $tag_name;
  }

}
