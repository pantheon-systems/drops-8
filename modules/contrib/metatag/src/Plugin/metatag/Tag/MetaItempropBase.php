<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

/**
 * This base plugin allows "itemprop"-style meta tags be customized.
 *
 * Used with e.g. the Google Plus tags.
 */
abstract class MetaItempropBase extends MetaNameBase {

  /**
   * {@inheritdoc}
   */
  protected $nameAttribute = 'itemprop';

}
