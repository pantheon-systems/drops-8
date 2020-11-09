<?php

namespace Drupal\Tests\metatag_favicons\Functional;

use Drupal\Tests\metatag\Functional\MetatagTagsTestBase;

/**
 * Tests that each of the Metatag Favicons tags work correctly.
 *
 * @group metatag
 */
class MetatagFaviconsTagsTest extends MetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected $tags = [
    'shortcut_icon',
    // 'mask_icon'.
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
  protected $testTag = 'link';

  /**
   * {@inheritdoc}
   */
  protected $testNameAttribute = 'rel';

  /**
   * {@inheritdoc}
   */
  protected $testValueAttribute = 'href';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::$modules[] = 'metatag_favicons';
    parent::setUp();
  }

  /**
   * Implements {tag_name}TestValueAttribute() for 'shortcut icon'.
   */
  protected function shortcutIconTestValueAttribute() {
    return 'href';
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'icon_16x16'.
   */
  protected function icon16x16TestOutputXpath() {
    return "//link[@rel='icon' and @sizes='16x16']";
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'icon_192x192'.
   */
  protected function icon192x192TestOutputXpath() {
    return "//link[@rel='icon' and @sizes='192x192']";
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'icon_32x32'.
   */
  protected function icon32x32TestOutputXpath() {
    return "//link[@rel='icon' and @sizes='32x32']";
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'icon_96x96'.
   */
  protected function icon96x96TestOutputXpath() {
    return "//link[@rel='icon' and @sizes='96x96']";
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'apple_touch_icon_precomposed'.
   */
  protected function appleTouchIconPrecomposedTestOutputXpath() {
    return "//link[@rel='apple-touch-icon-precomposed' and not(@sizes)]";
  }

  /**
   * Implements {tag_name}TestOutputXpath().
   *
   * For 'apple_touch_icon_precomposed_114x114'.
   */
  protected function appleTouchIconPrecomposed114x114TestOutputXpath() {
    return "//link[@rel='apple-touch-icon-precomposed' and @sizes='114x114']";
  }

  /**
   * Implements {tag_name}TestOutputXpath().
   *
   * For 'apple_touch_icon_precomposed_120x120'.
   */
  protected function appleTouchIconPrecomposed120x120TestOutputXpath() {
    return "//link[@rel='apple-touch-icon-precomposed' and @sizes='120x120']";
  }

  /**
   * Implements {tag_name}TestOutputXpath().
   *
   * For 'apple_touch_icon_precomposed_144x144'.
   */
  protected function appleTouchIconPrecomposed144x144TestOutputXpath() {
    return "//link[@rel='apple-touch-icon-precomposed' and @sizes='144x144']";
  }

  /**
   * Implements {tag_name}TestOutputXpath().
   *
   * For 'apple_touch_icon_precomposed_152x152'.
   */
  protected function appleTouchIconPrecomposed152x152TestOutputXpath() {
    return "//link[@rel='apple-touch-icon-precomposed' and @sizes='152x152']";
  }

  /**
   * Implements {tag_name}TestOutputXpath().
   *
   * For 'apple_touch_icon_precomposed_180x180'.
   */
  protected function appleTouchIconPrecomposed180x180TestOutputXpath() {
    return "//link[@rel='apple-touch-icon-precomposed' and @sizes='180x180']";
  }

  /**
   * Implements {tag_name}TestOutputXpath().
   *
   * For 'apple_touch_icon_precomposed_72x72'.
   */
  protected function appleTouchIconPrecomposed72x72TestOutputXpath() {
    return "//link[@rel='apple-touch-icon-precomposed' and @sizes='72x72']";
  }

  /**
   * Implements {tag_name}TestOutputXpath().
   *
   * For 'apple_touch_icon_precomposed_76x76'.
   */
  protected function appleTouchIconPrecomposed76x76TestOutputXpath() {
    return "//link[@rel='apple-touch-icon-precomposed' and @sizes='76x76']";
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'apple_touch_icon'.
   */
  protected function appleTouchIconTestOutputXpath() {
    return "//link[@rel='apple-touch-icon' and not(@sizes)]";
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'apple_touch_icon_114x114'.
   */
  protected function appleTouchIcon114x114TestOutputXpath() {
    return "//link[@rel='apple-touch-icon' and @sizes='114x114']";
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'apple_touch_icon_120x120'.
   */
  protected function appleTouchIcon120x120TestOutputXpath() {
    return "//link[@rel='apple-touch-icon' and @sizes='120x120']";
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'apple_touch_icon_144x144'.
   */
  protected function appleTouchIcon144x144TestOutputXpath() {
    return "//link[@rel='apple-touch-icon' and @sizes='144x144']";
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'apple_touch_icon_152x152'.
   */
  protected function appleTouchIcon152x152TestOutputXpath() {
    return "//link[@rel='apple-touch-icon' and @sizes='152x152']";
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'apple_touch_icon_180x180'.
   */
  protected function appleTouchIcon180x180TestOutputXpath() {
    return "//link[@rel='apple-touch-icon' and @sizes='180x180']";
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'apple_touch_icon_72x72'.
   */
  protected function appleTouchIcon72x72TestOutputXpath() {
    return "//link[@rel='apple-touch-icon' and @sizes='72x72']";
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'apple_touch_icon_76x76'.
   */
  protected function appleTouchIcon76x76TestOutputXpath() {
    return "//link[@rel='apple-touch-icon' and @sizes='76x76']";
  }

  /**
   * Implements {tag_name}TestOutputXpath for 'mask-icon'.
   */
  protected function maskIconTestTagName() {
    return 'mask-icon';
  }

  /**
   * Implements {tag_name}TestTagName for 'shortcut icon'.
   */
  protected function shortcutIconTestTagName() {
    return 'shortcut icon';
  }

}
