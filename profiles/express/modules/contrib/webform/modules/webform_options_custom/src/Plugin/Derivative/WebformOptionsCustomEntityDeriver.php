<?php

namespace Drupal\webform_options_custom\Plugin\Derivative;

/**
 * Provides webform custom options entity reference elements instances.
 *
 * @see \Drupal\webform_options_custom\Plugin\WebformElement\WebformOptionsCustom
 */
class WebformOptionsCustomEntityDeriver extends WebformOptionsCustomDeriverBase {

  /**
   * {@inheritdoc}
   */
  protected $type = 'entity_reference';

}
