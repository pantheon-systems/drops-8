<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\Plugin\WebformElementDisplayOnInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a base 'markup' element.
 */
abstract class WebformMarkupBase extends WebformElementBase implements WebformElementDisplayOnInterface {

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
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return [
      // Markup settings.
      'display_on' => static::DISPLAY_ON_FORM,
    ] + $this->getDefaultBaseProperties();
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

    // Add form element wrapper.
    if ($this->hasProperty('wrapper_attributes')) {
      $element['#theme_wrappers'][] = 'form_element';
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

    if ($this->isContainer($element)) {
      /** @var \Drupal\webform\WebformSubmissionViewBuilderInterface $view_builder */
      $view_builder = \Drupal::entityTypeManager()->getViewBuilder('webform_submission');
      $value = $view_builder->buildElements($element, $webform_submission, $options, 'html');

      // Since we are not passing this element to the
      // webform_container_base_html template we need to replace the default
      // sub elements with the value (i.e. renderable sub elements).
      if (is_array($value)) {
        $element = $value + $element;
      }
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

    if ($this->isContainer($element)) {
      // Must remove #prefix and #suffix.
      unset($element['#prefix'], $element['#suffix']);

      /** @var \Drupal\webform\WebformSubmissionViewBuilderInterface $view_builder */
      $view_builder = \Drupal::entityTypeManager()->getViewBuilder('webform_submission');
      $value = $view_builder->buildElements($element, $webform_submission, $options, 'text');

      // Since we are not passing this element to the
      // webform_container_base_text template we need to replace the default
      // sub elements with the value (i.e. renderable sub elements).
      if (is_array($value)) {
        $element = $value + $element;
      }
    }

    return $element;
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
    $form['markup'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Markup settings'),
    ];
    $form['markup']['display_on'] = [
      '#type' => 'select',
      '#title' => $this->t('Display on'),
      '#options' => $this->getDisplayOnOptions(),
    ];
    return $form;
  }

}
