<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

/**
 * This base plugin allows "http-equiv"-style meta tags, e.g. the content
 * language meta tag, to be further customized.
 */
abstract class MetaHttpEquivBase extends MetaNameBase {

  /**
   * {@inheritDoc}
   */
  protected $name_attribute = 'http-equiv';

}
