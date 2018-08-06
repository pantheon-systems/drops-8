<?php

namespace Drupal\metatag_dc_advanced\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\metatag\Tests\MetatagTagsTestBase;

/**
 * Tests that each of the Dublin Core Advanced tags work correctly.
 *
 * @group metatag
 */
class MetatagDublinCoreAdvancedTagsTest extends MetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  public $tags = [];

  /**
   * The tag to look for when testing the output.
   */
  public $test_tag = 'meta';

  /**
   * The attribute to look for to indicate which tag.
   */
  public $test_name_attribute = 'property';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::$modules[] = 'metatag_dc_advanced';
    parent::setUp();
  }

}
