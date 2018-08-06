<?php

namespace Drupal\entity_browser\Plugin\EntityBrowser\WidgetSelector;

use Drupal\entity_browser\WidgetSelectorBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Displays entity browser widgets as tabs.
 *
 * @EntityBrowserWidgetSelector(
 *   id = "tabs",
 *   label = @Translation("Tabs"),
 *   description = @Translation("Creates horizontal tabs on the top of the entity browser, each tab representing one available widget.")
 * )
 */
class Tabs extends WidgetSelectorBase {

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$form = array(), FormStateInterface &$form_state = NULL) {
    $element = [];
    foreach ($this->widget_ids as $id => $label) {
      $name = 'tab_selector_' . $id;
      $element[$name] = array(
        '#type' => 'button',
        '#attributes' => ['class' => ['tab']],
        '#value' => $label,
        '#disabled' => $id == $this->getDefaultWidget(),
        '#executes_submit_callback' => TRUE,
        '#limit_validation_errors' => array(array($id)),
        // #limit_validation_errors only takes effect if #submit is present.
        '#submit' => array(),
        '#name' => $name,
        '#widget_id' => $id,
      );
    }

    $element['#attached']['library'][] = 'entity_browser/tabs';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$form, FormStateInterface $form_state) {
    if (($trigger = $form_state->getTriggeringElement()) && strpos($trigger['#name'], 'tab_selector_') === 0) {
      if (!empty($this->widget_ids[$trigger['#widget_id']])) {
        return $trigger['#widget_id'];
      }
    }
  }

}
