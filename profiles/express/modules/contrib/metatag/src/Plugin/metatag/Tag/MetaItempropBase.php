<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

/**
 * This base plugin allows "itemprop"-style meta tags, e.g. Google plus tags, to
 * be further customized.
 */
abstract class MetaItempropBase extends MetaNameBase {

  /**
   * {@inheritDoc}
   */
  protected $name_attribute = 'itemprop';

}
