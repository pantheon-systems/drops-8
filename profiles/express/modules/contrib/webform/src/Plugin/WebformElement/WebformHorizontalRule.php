<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\Plugin\WebformElementDisplayOnInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'webform_horizontal_rule' element.
 *
 * @WebformElement(
 *   id = "webform_horizontal_rule",
 *   default_key = "horizontal_rule",
 *   label = @Translation("Horizontal rule"),
 *   description = @Translation("Provides a horizontal rule element."),
 *   category = @Translation("Markup elements"),
 *   states_wrapper = TRUE,
 * )
 */
class WebformHorizontalRule extends WebformElementBase implements WebformElementDisplayOnInterface {

  use WebformDisplayOnTrait;

  /**
   * {@inheritdoc}
   */
  public function isInput(array $element) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isContainer(array $element) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return [
      'states' => [],
      'attributes' => [],
      // Markup settings.
      'display_on' => static::DISPLAY_ON_FORM,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    // Hide element if it should not be displayed on 'form'.
    if (!$this->isDisplayOn($element, static::DISPLAY_ON_FORM)) {
      $element['#access'] = FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildHtml(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    // Hide element if it should not be displayed on 'view'.
    if (!$this->isDisplayOn($element, static::DISPLAY_ON_VIEW)) {
      return [];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function buildText(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    // Hide element if it should not be displayed on 'view'.
    if (!$this->isDisplayOn($element, static::DISPLAY_ON_VIEW)) {
      return [];
    }

    return PHP_EOL . '---' . PHP_EOL;
  }

  /**
   * {@inheritdoc}
   */
  public function getRelatedTypes(array $element) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorOptions(array $element) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['horizontal_rule'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Horizontal rule settings'),
    ];
    $form['horizontal_rule']['display_on'] = [
      '#type' => 'select',
      '#title' => $this->t('Display on'),
      '#options' => $this->getDisplayOnOptions(),
    ];

    $form['horizontal_rule_attributes'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Horizontal rule attributes'),
    ];
    $form['horizontal_rule_attributes']['attributes'] = [
      '#type' => 'webform_element_attributes',
      '#title' => $this->t('Horizontal rule'),
      '#classes' => $this->configFactory->get('webform.settings')->get('element.horizontal_rule_classes'),
    ];

    unset($form['element_attributes']['attributes']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return [
      '#type' => $this->getTypeName(),
      '#attributes' => [
        'class' => [
          'webform-horizontal-rule--dotted',
          'webform-horizontal-rule--thick',
        ],
      ],
    ];
  }
}
