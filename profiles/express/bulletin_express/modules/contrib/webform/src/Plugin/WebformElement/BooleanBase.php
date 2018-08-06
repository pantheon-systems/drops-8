<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\webform\WebformElementBase;

/**
 * Provides a base 'boolean' class.
 */
abstract class BooleanBase extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  public function formatTextItem(array $element, $value, array $options = []) {
    $format = $this->getItemFormat($element);

    switch ($format) {
      case 'value':
        return ($value) ? $this->t('Yes') : $this->t('No');

      default:
        return ($value) ? 1 : 0;
    }
  }

}
