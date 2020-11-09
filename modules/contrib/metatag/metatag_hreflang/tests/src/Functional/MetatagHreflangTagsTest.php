<?php

namespace Drupal\Tests\metatag_hreflang\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\metatag\Functional\MetatagTagsTestBase;

/**
 * Tests that each of the Metatag hreflang tags work correctly.
 *
 * @group metatag
 */
class MetatagHreflangTagsTest extends MetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected $tags = [
    'hreflang_xdefault',
    // @todo Work out how to do this as these values are kinda complicated.
    // 'hreflang_en',
    // 'hreflang_es',
    // 'hreflang_fr',
  ];

  /**
   * {@inheritdoc}
   */
  protected $testTag = 'link';

  /**
   * {@inheritdoc}
   */
  protected $testNameAttribute = 'alternate';

  /**
   * {@inheritdoc}
   */
  protected $testValueAttribute = 'href';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Need the Language module in order for any of this to work.
    parent::$modules[] = 'language';
    // This module.
    parent::$modules[] = 'metatag_hreflang';
    parent::setUp();

    // Enable additional languages.
    foreach (['es', 'fr'] as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }
  }

  /**
   * Each of these meta tags has a different tag name vs its internal name.
   */
  protected function getTestTagName($tag_name) {
    return str_replace('hreflang_', '', $tag_name);
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'hreflang_xdefault'.
   */
  protected function hreflangXdefaultTestOutputXpath() {
    return "//link[@hreflang='x-default']";
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'hreflang_en'.
   */
  protected function hreflangEnTestOutputXpath() {
    return "//link[@hreflang='en']";
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'hreflang_es'.
   */
  protected function hreflangEsTestOutputXpath() {
    return "//link[@hreflang='es']";
  }

  /**
   * Implements {tag_name}TestOutputXpath() for 'hreflang_fr'.
   */
  protected function hreflangFrTestOutputXpath() {
    return "//link[@hreflang='fr']";
  }

}
