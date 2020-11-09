<?php

namespace Drupal\Tests\metatag_mobile\Functional;

use Drupal\Tests\metatag\Functional\MetatagTagsTestBase;

/**
 * Tests that each of the Metatag mobile tags work correctly.
 *
 * @group metatag
 */
class MetatagMobileTagsTest extends MetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected $tags = [
    'alternate_handheld',
    'android_app_link_alternative',
    // @todo Write an update script to convert android_manifest to web_manifest
    // as these are the same tag.
    // 'android_manifest',
    'apple_itunes_app',
    'apple_mobile_web_app_capable',
    'apple_mobile_web_app_status_bar_style',
    'apple_mobile_web_app_title',
    'application_name',
    'cleartype',
    'format_detection',
    // @todo Remove the similar tag provided by core.
    // 'handheldfriendly',
    'ios_app_link_alternative',
    // @todo Remove the similar tag provided by core.
    // 'mobileoptimized',
    'msapplication_allowDomainApiCalls',
    'msapplication_allowDomainMetaTags',
    'msapplication_badge',
    'msapplication_config',
    'msapplication_navbutton_color',
    'msapplication_notification',
    'msapplication_square150x150logo',
    'msapplication_square310x310logo',
    'msapplication_square70x70logo',
    'msapplication_starturl',
    'msapplication_task',
    'msapplication_task_separator',
    'msapplication_tilecolor',
    'msapplication_tileimage',
    'msapplication_tooltip',
    'msapplication_wide310x150logo',
    'msapplication_window',
    'theme_color',
    'viewport',
    'web_manifest',
    'x_ua_compatible',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::$modules[] = 'metatag_mobile';
    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  protected function getTestTagName($tag_name) {
    // These tags all use dashes instead of underlines.
    $tag_name = str_replace('_', '-', $tag_name);

    // Fix a few specific tags.
    $tag_name = str_replace('android_manifest', 'manifest', $tag_name);
    $tag_name = str_replace('handheldfriendly', 'HandheldFriendly', $tag_name);
    $tag_name = str_replace('mobileoptimized', 'MobileOptimized', $tag_name);

    return $tag_name;
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'alternate-handheld'.
   */
  protected function alternateHandheldTestOutputXpath() {
    return "//link[@rel='alternate' and @media='handheld']";
  }

  /**
   * Implements {tag_name}TestValueAttribute() for 'alternate-handheld'.
   */
  protected function alternateHandheldTestValueAttribute() {
    return 'href';
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'amphtml'.
   */
  protected function amphtmlTestOutputXpath() {
    return "//link[@rel='amphtml']";
  }

  /**
   * Implements {tag_name}TestValueAttribute() for 'amphtml'.
   */
  protected function amphtmlTestValueAttribute() {
    return 'href';
  }

  /**
   * Implements {tag_name}TestValue() for 'android_app_link_alternative'.
   */
  protected function androidAppLinkAlternativeTestValue() {
    return 'android-app:' . $this->randomMachineName();
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'android-app-link-alternative'.
   */
  protected function androidAppLinkAlternativeTestOutputXpath() {
    return "//link[@rel='alternate' and starts-with(@href, 'android-app:')]";
  }

  /**
   * Implements {tag_name}TestValueAttribute().
   *
   * For 'android-app-link-alternative'.
   */
  protected function androidAppLinkAlternativeTestValueAttribute() {
    return 'href';
  }

  /**
   * Implements {tag_name}TestNameAttribute() for 'cleartype'.
   */
  protected function cleartypeTestNameAttribute() {
    return 'http-equiv';
  }

  /**
   * Implements {tag_name}TestValue() for 'ios_app_link_alternative'.
   */
  protected function iosAppLinkAlternativeTestValue() {
    return 'ios-app:' . $this->randomMachineName();
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'ios_app_link_alternative'.
   */
  protected function iosAppLinkAlternativeTestOutputXpath() {
    return "//link[@rel='alternate' and starts-with(@href, 'ios-app:')]";
  }

  /**
   * Implements {tag_name}TestValueAttribute() for 'ios_app_link_alternative'.
   */
  protected function iosAppLinkAlternativeTestValueAttribute() {
    return 'href';
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'mobileoptimized'.
   */
  protected function mobileoptimizedTestOutputXpath() {
    return "//meta[@name='MobileOptimized']";
  }

  /**
   * Implements {tag_name}TestValue() for 'msapplication-square150x150logo'.
   */
  protected function msapplicationSquare150x150logoTestValue() {
    return $this->randomImageUrl();
  }

  /**
   * Implements {tag_name}TestValue() for 'msapplication-square310x310logo'.
   */
  protected function msapplicationSquare310x310logoTestValue() {
    return $this->randomImageUrl();
  }

  /**
   * Implements {tag_name}TestValue() for 'msapplication-square70x70logo'.
   */
  protected function msapplicationSquare70x70logoTestValue() {
    return $this->randomImageUrl();
  }

  /**
   * Implements {tag_name}TestValue() for 'msapplication-tileimage'.
   */
  protected function msapplicationTileimageTestValue() {
    return $this->randomImageUrl();
  }

  /**
   * Implements {tag_name}TestValue() for 'msapplication-wide310x150logo'.
   */
  protected function msapplicationWide310x150logoTestValue() {
    return $this->randomImageUrl();
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'web_manifest'.
   */
  protected function webManifestTestOutputXpath() {
    return "//link[@rel='manifest']";
  }

  /**
   * Implements {tag_name}TestValueAttribute() for 'web_manifest'.
   */
  protected function webManifestTestValueAttribute() {
    return 'href';
  }

  /**
   * Implements {tag_name}TestNameAttribute() for 'x-ua-compatible'.
   */
  protected function xUaCompatibleTestNameAttribute() {
    return 'http-equiv';
  }

}
