<?php

namespace Drupal\entity_browser\Plugin\EntityBrowser\WidgetSelector;

use Drupal\entity_browser\WidgetSelectorBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Displays only first widget.
 *
 * @EntityBrowserWidgetSelector(
 *   id = "single",
 *   label = @Translation("Single widget"),
 *   description = @Translation("Displays only the first configured widget. Use this if you plan to have only one widget available.")
 * )
 */
class Single extends WidgetSelectorBase {

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$form, FormStateInterface &$form_state) {
    return array();
  }

}
