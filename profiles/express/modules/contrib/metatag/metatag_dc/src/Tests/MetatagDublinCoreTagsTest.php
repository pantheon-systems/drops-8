<?php

namespace Drupal\metatag_dc\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\metatag\Tests\MetatagTagsTestBase;

/**
 * Tests that each of the Dublin Core tags work correctly.
 *
 * @group metatag
 */
class MetatagDublinCoreTagsTest extends MetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  public $tags = [
    'dcterms_contributor',
    'dcterms_coverage',
    'dcterms_creator',
    'dcterms_date',
    'dcterms_description',
    'dcterms_format',
    'dcterms_identifier',
    'dcterms_language',
    'dcterms_publisher',
    'dcterms_relation',
    'dcterms_rights',
    'dcterms_source',
    'dcterms_subject',
    'dcterms_title',
    'dcterms_type',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::$modules[] = 'metatag_dc';
    parent::setUp();
  }

  /**
   * Each of these meta tags has a different tag name vs its internal name.
   */
  public function getTestTagName($tag_name) {
    return str_replace('_', '.', $tag_name);
  }

}
