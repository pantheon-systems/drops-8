<?php

namespace Drupal\entity_browser\Plugin\EntityBrowser\WidgetSelector;

use Drupal\entity_browser\WidgetSelectorBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Displays widgets in a select list.
 *
 * @EntityBrowserWidgetSelector(
 *   id = "drop_down",
 *   label = @Translation("Drop down widget"),
 *   description = @Translation("Displays the widgets in a drop down.")
 * )
 */
class DropDown extends WidgetSelectorBase {

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$form = array(), FormStateInterface &$form_state = NULL) {
    // Set a wrapper container for us to replace the form on ajax call.
    $form['#prefix'] = '<div id="entity-browser-form">';
    $form['#suffix'] = '</div>';

    $element['widget'] = array(
      '#type' => 'select',
      '#options' => $this->widget_ids,
      '#default_value' => $this->getDefaultWidget(),
      '#executes_submit_callback' => TRUE,
      '#limit_validation_errors' => array(array('widget')),
      // #limit_validation_errors only takes effect if #submit is present.
      '#submit' => array(),
      '#ajax' => array(
        'callback' => array($this, 'changeWidgetCallback'),
        'wrapper' => 'entity-browser-form',
      ),
    );

    $element['change'] = array(
      '#type' => 'submit',
      '#name' => 'change',
      '#value' => $this->t('Change'),
      '#attributes' => array('class' => array('js-hide')),
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$form, FormStateInterface $form_state) {
    return $form_state->getValue('widget');
  }

  /**
   * AJAX callback to refresh form.
   *
   * @param array $form
   *   Form.
   * @param FormStateInterface $form_state
   *   Form state object.
   *
   * @return array
   *   Form element to replace.
   */
  public function changeWidgetCallback(array &$form, FormStateInterface $form_state) {
    return $form;
  }

}
