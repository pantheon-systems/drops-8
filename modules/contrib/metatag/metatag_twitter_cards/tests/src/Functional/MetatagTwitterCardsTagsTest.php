<?php

namespace Drupal\Tests\metatag_twitter_cards\Functional;

use Drupal\Tests\metatag\Functional\MetatagTagsTestBase;

/**
 * Tests that each of the Metatag Twitter Cards tags work correctly.
 *
 * @group metatag
 */
class MetatagTwitterCardsTagsTest extends MetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected $tags = [
    'twitter_cards_app_id_googleplay',
    'twitter_cards_app_id_ipad',
    'twitter_cards_app_id_iphone',
    'twitter_cards_app_name_googleplay',
    'twitter_cards_app_name_ipad',
    'twitter_cards_app_name_iphone',
    'twitter_cards_app_store_country',
    'twitter_cards_app_url_googleplay',
    'twitter_cards_app_url_ipad',
    'twitter_cards_app_url_iphone',
    'twitter_cards_creator',
    'twitter_cards_creator_id',
    'twitter_cards_data1',
    'twitter_cards_data2',
    'twitter_cards_description',
    'twitter_cards_donottrack',
    'twitter_cards_image_alt',
    'twitter_cards_image_height',
    'twitter_cards_image_width',
    'twitter_cards_label1',
    'twitter_cards_label2',
    'twitter_cards_page_url',
    'twitter_cards_player_height',
    'twitter_cards_player_stream',
    'twitter_cards_player_stream_content_type',
    'twitter_cards_player_width',
    'twitter_cards_site',
    'twitter_cards_site_id',
    'twitter_cards_title',
    'twitter_cards_type',
    // @todo Fix test coverage for these tags.
    // 'twitter_cards_gallery_image0',
    // 'twitter_cards_gallery_image1',
    // 'twitter_cards_gallery_image2',
    // 'twitter_cards_gallery_image3',
    // 'twitter_cards_image',
    // 'twitter_cards_player',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::$modules[] = 'metatag_twitter_cards';
    parent::setUp();
  }

  /**
   * Twitter meta tags (almost) all have colons instead of underlines.
   *
   * They also don't have "cards" in their name.
   */
  protected function getTestTagName($tag_name) {
    $tag_name = str_replace('twitter_cards', 'twitter', $tag_name);
    $tag_name = str_replace('_', ':', $tag_name);

    if ($tag_name == 'twitter:app:store:country') {
      $tag_name = 'twitter:app:country';
    }
    elseif ($tag_name == 'twitter:page:url') {
      $tag_name = 'twitter:url';
    }
    elseif ($tag_name == 'twitter:player:stream:content:type') {
      $tag_name = 'twitter:player:stream:content_type';
    }
    elseif ($tag_name == 'twitter:type') {
      $tag_name = 'twitter:card';
    }
    elseif ($tag_name == 'twitter:donottrack') {
      $tag_name = 'twitter:dnt';
    }

    return $tag_name;
  }

  /**
   * Implements {tag_name}TestFieldXpath() for 'twitter_cards_type'.
   */
  protected function twitterCardsTypeTestFieldXpath() {
    return "//select[@name='twitter_cards_type']";
  }

  /**
   * Implements {tag_name}TestValue() for 'twitter_cards_type'.
   */
  protected function twitterCardsTypeTestValue() {
    return 'summary_large_image';
  }

}
