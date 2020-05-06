<?php

namespace Drupal\webform\Plugin\Menu\LocalAction;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\webform\Utility\WebformDialogHelper;

/**
 * Defines a local action plugin with the needed dialog attributes.
 */
class WebformDialogLocalAction extends LocalActionDefault {

  /**
   * {@inheritdoc}
   */
  public function getOptions(RouteMatchInterface $route_match) {
    $options = parent::getOptions($route_match);

    if (isset($this->pluginDefinition['dialog'])) {
      $attributes = WebformDialogHelper::getModalDialogAttributes($this->pluginDefinition['dialog']);
    }
    elseif (isset($this->pluginDefinition['off_canvas'])) {
      $attributes = WebformDialogHelper::getOffCanvasDialogAttributes($this->pluginDefinition['off_canvas']);
    }
    else {
      $attributes = [];
    }

    $options['attributes'] = (isset($this->pluginDefinition['attributes'])) ? $this->pluginDefinition['attributes'] : [];
    $options['attributes'] = NestedArray::mergeDeep($options['attributes'], $attributes);

    return $options;
  }

}
