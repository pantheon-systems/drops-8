<?php

namespace Drupal\metatag_favicons\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\metatag\Tests\MetatagTagsTestBase;

/**
 * Tests that each of the Metatag Favicons tags work correctly.
 *
 * @group metatag
 */
class MetatagFaviconsTagsTest extends MetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  public $tags = [
    'shortcut_icon',
    //'mask_icon',
    'icon_16x16',
    'icon_32x32',
    'icon_96x96',
    'icon_192x192',
    'apple_touch_icon',
    'apple_touch_icon_72x72',
    'apple_touch_icon_76x76',
    'apple_touch_icon_114x114',
    'apple_touch_icon_120x120',
    'apple_touch_icon_144x144',
    'apple_touch_icon_152x152',
    'apple_touch_icon_180x180',
    'apple_touch_icon_precomposed',
    'apple_touch_icon_precomposed_72x72',
    'apple_touch_icon_precomposed_76x76',
    'apple_touch_icon_precomposed_114x114',
    'apple_touch_icon_precomposed_120x120',
    'apple_touch_icon_precomposed_144x144',
    'apple_touch_icon_precomposed_152x152',
    'apple_touch_icon_precomposed_180x180',
  ];

  /**
   * {@inheritdoc}
   */
  public $test_tag = 'link';

  /**
   * {@inheritdoc}
   */
  public $test_name_attribute = 'rel';

  /**
   * {@inheritdoc}
   */
  public $test_value_attribute = 'href';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::$modules[] = 'metatag_favicons';
    parent::setUp();
  }

  /**
   * Implements {meta_tag_name}_test_value_attribute() for
   * 'shortcut icon'.
   */
  public function shortcut_icon_test_value_attribute() {
    return 'href';
  }

  /**
   * Implements {meta_tag_name}_test_output_xpath() for 'icon_16x16',
   */
  public function icon_16x16_test_output_xpath() {
    return "//link[@rel='icon' and @sizes='16x16']";
  }

  /**
   * Implements {meta_tag_name}_test_output_xpath() for 'icon_192x192',
   */
  public function icon_192x192_test_output_xpath() {
    return "//link[@rel='icon' and @sizes='192x192']";
  }

  /**
   * Implements {meta_tag_name}_test_output_xpath() for 'icon_32x32',
   */
  public function icon_32x32_test_output_xpath() {
    return "//link[@rel='icon' and @sizes='32x32']";
  }

  /**
   * Implements {meta_tag_name}_test_output_xpath() for 'icon_96x96',
   */
  public function icon_96x96_test_output_xpath() {
    return "//link[@rel='icon' and @sizes='96x96']";
  }

  /**
   * Implements {meta_tag_name}_test_output_xpath() for
   * 'apple_touch_icon_precomposed',
   */
  public function apple_touch_icon_precomposed_test_output_xpath() {
    return "//link[@rel='apple-touch-icon-precomposed' and not(@sizes)]";
  }

  /**
   * Implements {meta_tag_name}_test_output_xpath() for
   * 'apple_touch_icon_precomposed_114x114',
   */
  public function apple_touch_icon_precomposed_114x114_test_output_xpath() {
    return "//link[@rel='apple-touch-icon-precomposed' and @sizes='114x114']";
  }

  /**
   * Implements {meta_tag_name}_test_output_xpath() for
   * 'apple_touch_icon_precomposed_120x120',
   */
  public function apple_touch_icon_precomposed_120x120_test_output_xpath() {
    return "//link[@rel='apple-touch-icon-precomposed' and @sizes='120x120']";
  }

  /**
   * Implements {meta_tag_name}_test_output_xpath() for
   * 'apple_touch_icon_precomposed_144x144',
   */
  public function apple_touch_icon_precomposed_144x144_test_output_xpath() {
    return "//link[@rel='apple-touch-icon-precomposed' and @sizes='144x144']";
  }

  /**
   * Implements {meta_tag_name}_test_output_xpath() for
   * 'apple_touch_icon_precomposed_152x152',
   */
  public function apple_touch_icon_precomposed_152x152_test_output_xpath() {
    return "//link[@rel='apple-touch-icon-precomposed' and @sizes='152x152']";
  }

  /**
   * Implements {meta_tag_name}_test_output_xpath() for
   * 'apple_touch_icon_precomposed_180x180',
   */
  public function apple_touch_icon_precomposed_180x180_test_output_xpath() {
    return "//link[@rel='apple-touch-icon-precomposed' and @sizes='180x180']";
  }

  /**
   * Implements {meta_tag_name}_test_output_xpath() for
   * 'apple_touch_icon_precomposed_72x72',
   */
  public function apple_touch_icon_precomposed_72x72_test_output_xpath() {
    return "//link[@rel='apple-touch-icon-precomposed' and @sizes='72x72']";
  }

  /**
   * Implements {meta_tag_name}_test_output_xpath() for
   * 'apple_touch_icon_precomposed_76x76',
   */
  public function apple_touch_icon_precomposed_76x76_test_output_xpath() {
    return "//link[@rel='apple-touch-icon-precomposed' and @sizes='76x76']";
  }

  /**
   * Implements {meta_tag_name}_test_output_xpath() for 'apple_touch_icon',
   */
  public function apple_touch_icon_test_output_xpath() {
    return "//link[@rel='apple-touch-icon' and not(@sizes)]";
  }

  /**
   * Implements {meta_tag_name}_test_output_xpath() for
   * 'apple_touch_icon_114x114',
   */
  public function apple_touch_icon_114x114_test_output_xpath() {
    return "//link[@rel='apple-touch-icon' and @sizes='114x114']";
  }

  /**
   * Implements {meta_tag_name}_test_output_xpath() for
   * 'apple_touch_icon_120x120',
   */
  public function apple_touch_icon_120x120_test_output_xpath() {
    return "//link[@rel='apple-touch-icon' and @sizes='120x120']";
  }

  /**
   * Implements {meta_tag_name}_test_output_xpath() for
   * 'apple_touch_icon_144x144',
   */
  public function apple_touch_icon_144x144_test_output_xpath() {
    return "//link[@rel='apple-touch-icon' and @sizes='144x144']";
  }

  /**
   * Implements {meta_tag_name}_test_output_xpath() for
   * 'apple_touch_icon_152x152',
   */
  public function apple_touch_icon_152x152_test_output_xpath() {
    return "//link[@rel='apple-touch-icon' and @sizes='152x152']";
  }

  /**
   * Implements {meta_tag_name}_test_output_xpath() for
   * 'apple_touch_icon_180x180',
   */
  public function apple_touch_icon_180x180_test_output_xpath() {
    return "//link[@rel='apple-touch-icon' and @sizes='180x180']";
  }

  /**
   * Implements {meta_tag_name}_test_output_xpath() for
   * 'apple_touch_icon_72x72',
   */
  public function apple_touch_icon_72x72_test_output_xpath() {
    return "//link[@rel='apple-touch-icon' and @sizes='72x72']";
  }

  /**
   * Implements {meta_tag_name}_test_output_xpath() for
   * 'apple_touch_icon_76x76',
   */
  public function apple_touch_icon_76x76_test_output_xpath() {
    return "//link[@rel='apple-touch-icon' and @sizes='76x76']";
  }

  /**
   * Implements {meta_tag_name}_test_output_xpath for 'mask-icon'.
   */
  public function mask_icon_test_tag_name() {
    return 'mask-icon';
  }

  /**
   * Implements {meta_tag_name}_test_tag_name for 'shortcut icon'.
   */
  public function shortcut_icon_test_tag_name() {
    return 'shortcut icon';
  }

}
