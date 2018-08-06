<?php

/**
 * @file
 * Contains \Drupal\linkit\Element\Linkit.
 */

namespace Drupal\linkit\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Render\Element\Textfield;
use Drupal\Core\Url;

/**
 * Provides a form element for linkit.
 *
 * @FormElement("linkit")
 */
class Linkit extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#size' => 60,
      '#process' => array(
        array($class, 'processLinkitAutocomplete'),
        array($class, 'processGroup'),
      ),
      '#pre_render' => array(
        array($class, 'preRenderLinkitElement'),
        array($class, 'preRenderGroup'),
      ),
      '#theme' => 'input__textfield',
      '#theme_wrappers' => array('form_element'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    return Textfield::valueCallback($element, $input, $form_state);
  }

  /**
   * Adds linkit custom autocomplete functionality to elements.
   *
   * Instead of using the core autocomplete, we use our own.
   *
   * {@inheritdoc}
   *
   * @see \Drupal\Core\Render\Element\FormElement::processAutocomplete
   */
  public static function processLinkitAutocomplete(&$element, FormStateInterface $form_state, &$complete_form) {
    $url = NULL;
    $access = FALSE;

    if (!empty($element['#autocomplete_route_name'])) {
      $parameters = isset($element['#autocomplete_route_parameters']) ? $element['#autocomplete_route_parameters'] : array();
      $url = Url::fromRoute($element['#autocomplete_route_name'], $parameters)->toString(TRUE);
      /** @var \Drupal\Core\Access\AccessManagerInterface $access_manager */
      $access_manager = \Drupal::service('access_manager');
      $access = $access_manager->checkNamedRoute($element['#autocomplete_route_name'], $parameters, \Drupal::currentUser(), TRUE);
    }

    if ($access) {
      $metadata = BubbleableMetadata::createFromRenderArray($element);
      if ($access->isAllowed()) {
        $element['#attributes']['class'][] = 'form-linkit-autocomplete';
        $metadata->addAttachments(['library' => ['linkit/linkit.autocomplete']]);
        // Provide a data attribute for the JavaScript behavior to bind to.
        $element['#attributes']['data-autocomplete-path'] = $url->getGeneratedUrl();
        $metadata = $metadata->merge($url);
      }
      $metadata
        ->merge(BubbleableMetadata::createFromObject($access))
        ->applyTo($element);
    }

    return $element;
  }

  /**
   * Prepares a #type 'linkit' render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #size, #attributes.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderLinkitElement($element) {
    $element['#attributes']['type'] = 'text';
    Element::setAttributes($element, array('id', 'name', 'value', 'size'));
    static::setAttributes($element, array('form-text'));

    return $element;
  }

}
