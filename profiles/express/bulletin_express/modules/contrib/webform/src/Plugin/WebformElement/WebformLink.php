<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormState;
use Drupal\webform\Element\WebformLink as WebformLinkElement;

/**
 * Provides a 'link' element.
 *
 * @WebformElement(
 *   id = "webform_link",
 *   label = @Translation("Link"),
 *   category = @Translation("Composite elements"),
 *   description = @Translation("Provides a form element to display a link."),
 *   composite = TRUE,
 * )
 */
class WebformLink extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  protected function getCompositeElements() {
    return WebformLinkElement::getCompositeElements();
  }

  /**
   * {@inheritdoc}
   */
  protected function getInitializedCompositeElement(array &$element) {
    $form_state = new FormState();
    $form_completed = [];
    return WebformLinkElement::processWebformComposite($element, $form_state, $form_completed);
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItemValue(array $element, array $value) {
    return [
      'link' => [
        '#type' => 'link',
        '#title' => $value['title'],
        '#url' => \Drupal::pathValidator()->getUrlIfValid($value['url']),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItemValue(array $element, array $value) {
    return [
      'link' => new FormattableMarkup('@title (@url)', ['@title' => $value['title'], '@url' => $value['url']]),
    ];
  }

}
