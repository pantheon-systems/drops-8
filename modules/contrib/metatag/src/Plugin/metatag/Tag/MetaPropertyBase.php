<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

/**
 * This base plugin allows "property"-style meta tags tobe customized.
 *
 * Used with e.g. the Open Graph tags.
 */
abstract class MetaPropertyBase extends MetaNameBase {

  /**
   * {@inheritdoc}
   */
  protected $nameAttribute = 'property';

}
