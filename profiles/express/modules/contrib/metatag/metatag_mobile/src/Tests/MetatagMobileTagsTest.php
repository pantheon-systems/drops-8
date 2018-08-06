<?php

namespace Drupal\metatag_mobile\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\metatag\Tests\MetatagTagsTestBase;

/**
 * Tests that each of the Metatag mobile tags work correctly.
 *
 * @group metatag
 */
class MetatagMobileTagsTest extends MetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  public $tags = [
    'android_app_link_alternative',
    'android_manifest',
    'apple_itunes_app',
    'apple_mobile_web_app_capable',
    'apple_mobile_web_app_status_bar_style',
    'apple_mobile_web_app_title',
    'application_name',
    'cleartype',
    'format_detection',
    'handheldfriendly',
    'ios_app_link_alternative',
    'mobileoptimized',
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
  public function getTestTagName($tag_name) {
    // These tags all use dashes instead of underlines.
    $tag_name = str_replace('_', '-', $tag_name);

    // Fix a few specific tags.
    $tag_name = str_replace('mobileoptimized', 'MobileOptimized', $tag_name);
    $tag_name = str_replace('handheldfriendly', 'HandheldFriendly', $tag_name);

    return $tag_name;
  }

  /**
   * Implements {meta_tag_name}_test_output_xpath() for 'alternate-handheld'.
   */
  public function alternate_handheld_test_output_xpath() {
    return "//link[@rel='alternate' and @media='handheld']";
  }

  /**
   * Implements {meta_tag_name}_test_value_attribute() for 'alternate-handheld'.
   */
  public function alternate_handheld_test_value_attribute() {
    return 'href';
  }

  /**
   * Implements {meta_tag_name}_test_output_xpath() for 'amphtml'.
   */
  public function amphtml_test_output_xpath() {
    return "//link[@rel='amphtml']";
  }

  /**
   * Implements {meta_tag_name}_test_value_attribute() for 'amphtml'.
   */
  public function amphtml_test_value_attribute() {
    return 'href';
  }

  /**
   * Implements {meta_tag_name}_test_value() for 'android_app_link_alternative'.
   */
  public function android_app_link_alternative_test_value() {
    return 'android-app:' . $this->randomMachineName();
  }

  /**
   * Implements {meta_tag_name}_test_output_xpath() for
   * 'android-app-link-alternative'.
   */
  public function android_app_link_alternative_test_output_xpath() {
    return "//link[@rel='alternate' and starts-with(@href, 'android-app:')]";
  }

  /**
   * Implements {meta_tag_name}_test_preprocess_output() for
   * 'android-app-link-alternative'.
   */
  public function android_app_link_alternative_test_preprocess_output($string) {
    return 'android-app://' . $string;
  }

  /**
   * Implements {meta_tag_name}_test_value_attribute() for
   * 'android-app-link-alternative'.
   */
  public function android_app_link_alternative_test_value_attribute() {
    return 'href';
  }

  /**
   * Implements {meta_tag_name}_test_output_xpath() for 'android_manifest'.
   */
  public function android_manifest_test_output_xpath() {
    return "//link[@rel='manifest']";
  }

  /**
   * Implements {meta_tag_name}_test_value_attribute() for 'android_manifest'.
   */
  public function android_manifest_test_value_attribute() {
    return 'href';
  }

  /**
   * Implements {meta_tag_name}_test_name_attribute() for 'cleartype'.
   */
  public function cleartype_test_name_attribute() {
    return 'http-equiv';
  }

  /**
   * Implements {meta_tag_name}_test_output_xpath() for 'handheldfriendly'.
   */
  public function handheldfriendly_test_output_xpath() {
    return "//meta[@name='HandheldFriendly']";
  }

  /**
   * Implements {meta_tag_name}_test_value() for
   * 'ios_app_link_alternative'.
   */
  public function ios_app_link_alternative_test_value() {
    return 'ios-app:' . $this->randomMachineName();
  }

  /**
   * Implements {meta_tag_name}_test_output_xpath() for
   * 'ios_app_link_alternative'.
   */
  public function ios_app_link_alternative_test_output_xpath() {
    return "//link[@rel='alternate' and starts-with(@href, 'ios-app:')]";
  }

  /**
   * Implements {meta_tag_name}_test_output_prefix() for
   * 'ios_app_link_alternative'.
   */
  public function ios_app_link_alternative_test_preprocess_output($string) {
    return 'ios-app://' . $string;
  }

  /**
   * Implements {meta_tag_name}_test_value_attribute() for
   * 'ios_app_link_alternative'.
   */
  public function ios_app_link_alternative_test_value_attribute() {
    return 'href';
  }

  /**
   * Implements {meta_tag_name}_test_output_xpath() for 'mobileoptimized'.
   */
  public function mobileoptimized_test_output_xpath() {
    return "//meta[@name='MobileOptimized']";
  }

  /**
   * Implements {meta_tag_name}_test_value() for 'msapplication-square150x150logo'.
   */
  public function msapplication_square150x150logo_test_value() {
    return $this->randomImageUrl();
  }

  /**
   * Implements {meta_tag_name}_test_value() for 'msapplication-square310x310logo'.
   */
  public function msapplication_square310x310logo_test_value() {
    return $this->randomImageUrl();
  }

  /**
   * Implements {meta_tag_name}_test_value() for 'msapplication-square70x70logo'.
   */
  public function msapplication_square70x70logo_test_value() {
    return $this->randomImageUrl();
  }

  /**
   * Implements {meta_tag_name}_test_value() for 'msapplication-tileimage'.
   */
  public function msapplication_tileimage_test_value() {
    return $this->randomImageUrl();
  }

  /**
   * Implements {meta_tag_name}_test_value() for 'msapplication-wide310x150logo'.
   */
  public function msapplication_wide310x150logo_test_value() {
    return $this->randomImageUrl();
  }

  /**
   * Implements {meta_tag_name}_test_name_attribute() for 'x-ua-compatible'.
   */
  public function x_ua_compatible_test_name_attribute() {
    return 'http-equiv';
  }

}
