<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides an 'display_on' trait.
 */
trait WebformDisplayOnTrait {

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

    return parent::buildHtml($element, $webform_submission, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function buildText(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    // Hide element if it should not be displayed on 'view'.
    if (!$this->isDisplayOn($element, static::DISPLAY_ON_VIEW)) {
      return [];
    }
    return parent::buildText($element, $webform_submission, $options);
  }

  /**
   * Check is the element is display on form, view, or both.
   *
   * @param array $element
   *   An element.
   * @param string $display_on
   *   Display on form or view.
   *
   * @return bool
   *   TRUE if the element should be displayed on the form or view.
   */
  protected function isDisplayOn(array $element, $display_on) {
    $element_display_on = (isset($element['#display_on'])) ? $element['#display_on'] : $this->getDefaultProperty('display_on');
    return ($element_display_on == static::DISPLAY_ON_BOTH || $element_display_on == $display_on) ? TRUE : FALSE;
  }

  /**
   * Get display on options.
   *
   * @param bool $none
   *   If TRUE none is include.
   *
   * @return array
   *   An associative array of display on options.
   */
  protected function getDisplayOnOptions($none = FALSE) {
    $options = [
      static::DISPLAY_ON_FORM => $this->t('form only'),
      static::DISPLAY_ON_VIEW => $this->t('viewed submission only'),
      static::DISPLAY_ON_BOTH => $this->t('both form and viewed submission'),
    ];
    if ($none) {
      $options[static::DISPLAY_ON_NONE] = $this->t('none');
    }
    return $options;
  }

}
