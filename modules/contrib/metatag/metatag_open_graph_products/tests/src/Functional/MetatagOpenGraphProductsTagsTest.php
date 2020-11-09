<?php

namespace Drupal\Tests\metatag_open_graph_products\Functional;

use Drupal\Tests\metatag\Functional\MetatagTagsTestBase;

/**
 * Tests that each of the Metatag Open Graph Product tags work correctly.
 *
 * @group metatag
 */
class MetatagOpenGraphProductsTagsTest extends MetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected $tags = [
    'product_price_amount',
    'product_price_currency',
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
    parent::$modules[] = 'metatag_open_graph_products';
    parent::setUp();
  }

  /**
   * Each of these meta tags has a different tag name vs its internal name.
   */
  protected function getTestTagName($tag_name) {
    // Replace the underlines with a colon.
    $tag_name = str_replace('_', ':', $tag_name);

    return $tag_name;
  }

}
