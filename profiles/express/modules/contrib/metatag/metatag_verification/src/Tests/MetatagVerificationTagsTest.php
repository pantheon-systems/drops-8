<?php

namespace Drupal\metatag_verification\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\metatag\Tests\MetatagTagsTestBase;

/**
 * Tests that each of the Metatag Verification tags work correctly.
 *
 * @group metatag
 */
class MetatagVerificationTagsTest extends MetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  public $tags = [
    'baidu',
    'bing',
    'google',
    'norton_safe_web',
    'pinterest',
    'yandex',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::$modules[] = 'metatag_verification';
    parent::setUp();
  }

  /**
   * Each of these meta tags has a different tag name vs its internal name.
   */
  public function getTestTagName($tag_name) {
    if ($tag_name == 'baidu') {
      $tag_name = 'baidu-site-verification';
    }
    elseif ($tag_name == 'bing') {
      $tag_name = 'msvalidate.01';
    }
    elseif ($tag_name == 'google') {
      $tag_name = 'google-site-verification';
    }
    elseif ($tag_name == 'norton_safe_web') {
      $tag_name = 'norton-safeweb-site-verification';
    }
    elseif ($tag_name == 'pinterest') {
      $tag_name = 'p:domain_verify';
    }
    elseif ($tag_name == 'yandex') {
      $tag_name = 'yandex-verification';
    }

    return $tag_name;
  }

}
