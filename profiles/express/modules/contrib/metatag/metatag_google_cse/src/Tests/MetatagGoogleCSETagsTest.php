<?php

namespace Drupal\metatag_google_cse\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\metatag\Tests\MetatagTagsTestBase;

/**
 * Tests that each of the Metatag Google CSE tags work correctly.
 *
 * @group metatag
 */
class MetatagGoogleCSETagsTest extends MetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  public $tags = [
    'audience',
    'department',
    'doc_status',
    'google_rating',
    'thumbnail',
  ];

  /**
   * The attribute to look for to indicate which tag.
   */
  // public $test_name_attribute = 'property';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::$modules[] = 'metatag_google_cse';
    parent::setUp();
  }

  /**
   * Implements {meta_tag_name}_test_tag_name() for 'google_rating'.
   */
  public function google_rating_test_tag_name() {
    return 'rating';
  }

}
