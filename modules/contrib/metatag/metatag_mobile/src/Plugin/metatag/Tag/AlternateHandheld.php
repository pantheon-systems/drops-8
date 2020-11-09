<?php

namespace Drupal\metatag_mobile\Plugin\metatag\Tag;

/**
 * The alternate_handheld meta tag.
 *
 * @MetatagTag(
 *   id = "alternate_handheld",
 *   label = @Translation("Handheld URL"),
 *   description = @Translation("Provides an absolute URL to a specially formatted version of the current page designed for 'feature phones', mobile phones that do not support modern browser standards. See the <a href='https://developers.google.com/webmasters/mobile-sites/mobile-seo/other-devices?hl=en#feature_phones'>official Google Mobile SEO Guide</a> for details on how the page should be formatted."),
 *   name = "alternate",
 *   group = "mobile",
 *   weight = 8,
 *   type = "uri",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AlternateHandheld extends LinkMediaBase {

  /**
   * {@inheritdoc}
   */
  protected function media() {
    return 'handheld';
  }

}
