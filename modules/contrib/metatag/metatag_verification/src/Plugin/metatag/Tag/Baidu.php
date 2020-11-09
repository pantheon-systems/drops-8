<?php

namespace Drupal\metatag_verification\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'baidu-site-verification' meta tag.
 *
 * @MetatagTag(
 *   id = "baidu",
 *   label = @Translation("Baidu"),
 *   description = @Translation("A string provided by <a href=':baidu'>Baidu</a>.", arguments = { ":baidu"  = "https://www.baidu.com/" }),
 *   name = "baidu-site-verification",
 *   group = "site_verification",
 *   weight = 2,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Baidu extends MetaNameBase {
}
