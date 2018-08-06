<?php

namespace Drupal\metatag_open_graph\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\metatag\Tests\MetatagTagsTestBase;

/**
 * Tests that each of the Metatag Open Graph tags work correctly.
 *
 * @group metatag
 */
class MetatagOpenGraphTagsTest extends MetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  public $tags = [
    'article_author',
    'article_expiration_time',
    'article_modified_time',
    'article_published_time',
    'article_publisher',
    'article_section',
    'article_tag',
    'og_country_name',
    'og_description',
    'og_determiner',
    'og_email',
    'og_fax_number',
    'og_image',
    'og_image_height',
    'og_image_secure_url',
    'og_image_type',
    'og_image_url',
    'og_image_width',
    'og_latitude',
    'og_locale',
    'og_locale_alternative',
    'og_locality',
    'og_longitude',
    'og_phone_number',
    'og_postal_code',
    'og_region',
    'og_see_also',
    'og_site_name',
    'og_street_address',
    'og_title',
    'og_type',
    'og_updated_time',
    'og_url',
    'og_video',
    'og_video_height',
    'og_video_secure_url',
    'og_video_type',
    'og_video_width',
  ];

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
    parent::$modules[] = 'metatag_open_graph';
    parent::setUp();
  }

  /**
   * Each of these meta tags has a different tag name vs its internal name.
   */
  public function getTestTagName($tag_name) {
    // Replace the first underline with a colon.
    $tag_name = str_replace('og_', 'og:', $tag_name);
    $tag_name = str_replace('article_', 'article:', $tag_name);

    // Some tags have an additional underline that turns into a colon.
    $tag_name = str_replace('og:image_', 'og:image:', $tag_name);
    $tag_name = str_replace('og:video_', 'og:video:', $tag_name);

    // Additional fixes.
    if ($tag_name == 'og:locale_alternative') {
      $tag_name = 'og:locale:alternate';
    }

    return $tag_name;
  }

}
