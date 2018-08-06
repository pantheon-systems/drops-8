<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

/**
 * This base plugin allows "property"-style meta tags, e.g. Open Graph tags, to
 * be further customized.
 */
abstract class MetaPropertyBase extends MetaNameBase {

  /**
   * {@inheritDoc}
   */
  protected $name_attribute = 'property';

}
