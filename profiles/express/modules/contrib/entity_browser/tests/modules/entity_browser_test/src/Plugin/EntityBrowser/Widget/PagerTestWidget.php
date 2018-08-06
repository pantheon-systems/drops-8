<?php

namespace Drupal\entity_browser_test\Plugin\EntityBrowser\Widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\Element\EntityBrowserPagerElement;
use Drupal\entity_browser\WidgetBase;

/**
 * Pager test widget implementation for test purposes.
 *
 * @EntityBrowserWidget(
 *   id = "pager_test",
 *   label = @Translation("Pager test widget"),
 *   description = @Translation("Pager test widget existing for testing purposes."),
 *   auto_select = FALSE
 * )
 */
class PagerTestWidget extends WidgetBase {

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

    $form['message'] = [
      '#markup' => $this->t('Current page reported by the element is: @page.', ['@page' => EntityBrowserPagerElement::getCurrentPage($form_state)]),
    ];

    $form['first'] = [
      '#type' => 'submit',
      '#value' => $this->t('First page'),
      '#submit' => [[static::class, 'submitFirst']],
    ];

    $form['last'] = [
      '#type' => 'submit',
      '#value' => $this->t('Last page'),
      '#submit' => [[static::class, 'submitLast']],
    ];

    $form['pager_eb'] = [
      '#type' => 'entity_browser_pager',
      '#total_pages' => 4,
    ];

    return $form;
  }

  /**
   * Submit callback for first page reset button.
   */
  public function submitFirst(array &$form, FormStateInterface $form_state) {
    EntityBrowserPagerElement::setCurrentPage($form_state);
    $form_state->setRebuild();
  }

  /**
   * Submit callback for last page reset button.
   */
  public function submitLast(array &$form, FormStateInterface $form_state) {
    EntityBrowserPagerElement::setCurrentPage($form_state, 4);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntities(array $form, FormStateInterface $form_state) {}

}
