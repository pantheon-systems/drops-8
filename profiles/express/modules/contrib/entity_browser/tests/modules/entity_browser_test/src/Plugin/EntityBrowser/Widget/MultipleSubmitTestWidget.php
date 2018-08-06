<?php

namespace Drupal\entity_browser_test\Plugin\EntityBrowser\Widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\WidgetBase;

/**
 * Test widget with multiple submit buttons for test purposes.
 *
 * @EntityBrowserWidget(
 *   id = "multiple_submit_test_widget",
 *   label = @Translation("Multiple submit test widget"),
 *   description = @Translation("Test widget with multiple submit buttons only for testing purposes."),
 *   auto_select = FALSE
 * )
 */
class MultipleSubmitTestWidget extends WidgetBase {

  /**
   * Entity to be returned.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  public $entity;

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);

    $form['submit_second'] = [
      '#type' => 'submit',
      '#value' => $this->t('Second submit button'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntities(array $form, FormStateInterface $form_state) {
    return $form_state->getValue('dummy_entities', []);
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    if ($this->entity) {
      $this->selectEntities([$this->entity], $form_state);
    }
  }

}
