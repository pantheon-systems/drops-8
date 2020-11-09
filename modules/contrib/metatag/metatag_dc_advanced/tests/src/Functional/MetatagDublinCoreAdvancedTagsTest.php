<?php

namespace Drupal\Tests\metatag_dc_advanced\Functional;

use Drupal\Tests\metatag\Functional\MetatagTagsTestBase;
use Symfony\Component\DependencyInjection\Container;

/**
 * Tests that each of the Dublin Core Advanced tags work correctly.
 *
 * @group metatag
 */
class MetatagDublinCoreAdvancedTagsTest extends MetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected $tags = [
    'dcterms_abstract',
    'dcterms_access_rights',
    'dcterms_accrual_method',
    'dcterms_accrual_periodicity',
    'dcterms_accrual_policy',
    'dcterms_alternative',
    'dcterms_audience',
    'dcterms_available',
    'dcterms_bibliographic_citation',
    'dcterms_conforms_to',
    'dcterms_created',
    'dcterms_date_accepted',
    'dcterms_date_copyrighted',
    'dcterms_date_submitted',
    'dcterms_education_level',
    'dcterms_extent',
    'dcterms_has_format',
    'dcterms_has_part',
    'dcterms_has_version',
    'dcterms_instructional_method',
    'dcterms_is_format_of',
    'dcterms_is_part_of',
    'dcterms_is_referenced_by',
    'dcterms_is_replaced_by',
    'dcterms_is_required_by',
    'dcterms_issued',
    'dcterms_is_version_of',
    'dcterms_license',
    'dcterms_mediator',
    'dcterms_medium',
    'dcterms_modified',
    'dcterms_provenance',
    'dcterms_references',
    'dcterms_replaces',
    'dcterms_requires',
    'dcterms_rights_holder',
    'dcterms_spatial',
    'dcterms_table_of_contents',
    'dcterms_temporal',
    'dcterms_valid',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::$modules[] = 'metatag_dc_advanced';
    parent::setUp();
  }

  /**
   * Each of these meta tags has a different tag name vs its internal name.
   */
  protected function getTestTagName($tag_name) {
    $tag_name = str_replace('dcterms_', '', $tag_name);
    $tag_name = lcfirst(Container::camelize($tag_name));
    return 'dcterms.' . $tag_name;
  }

}
