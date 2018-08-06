<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Locale\CountryManager;
use Drupal\webform\Element\WebformTelephone as WebformTelephoneElement;

/**
 * Provides a 'telephone' (composite) element.
 *
 * @WebformElement(
 *   id = "webform_telephone",
 *   label = @Translation("Telephone advanced"),
 *   category = @Translation("Composite elements"),
 *   description = @Translation("Provides a form element to display a telephone number with type and extension."),
 *   composite = TRUE,
 * )
 */
class WebformTelephone extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $default_properties = parent::getDefaultProperties();
    $default_properties['title_display'] = '';
    $default_properties['phone__international'] = TRUE;
    $default_properties['phone__international_initial_country'] = '';
    unset($default_properties['flexbox']);
    return $default_properties;
  }

  /**
   * {@inheritdoc}
   */
  protected function getCompositeElements() {
    return WebformTelephoneElement::getCompositeElements();
  }

  /**
   * {@inheritdoc}
   */
  protected function getInitializedCompositeElement(array &$element) {
    $form_state = new FormState();
    $form_completed = [];
    return WebformTelephoneElement::processWebformComposite($element, $form_state, $form_completed);
  }

  /**
   * {@inheritdoc}
   */
  protected function getCompositeElementOptions($composite_key) {
    if ($composite_key === 'type') {
      $composite_key = 'phone_type';
    }
    return parent::getCompositeElementOptions($composite_key);
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItemValue(array $element, array $value) {
    $t_args = [
      ':tel' => 'tel:' . $value['phone'],
      '@tel' => $value['phone'],
      '@ext' => $value['ext'],
      '@type' => $value['type'],
    ];
    if ($value['ext'] && $value['type']) {
      $telephone = $this->t('<b>@type:</b> <a href=":tel">@tel</a> x@ext', $t_args);
    }
    elseif ($value['ext']) {
      $telephone = $this->t('<a href=":tel">@tel</a> x@ext', $t_args);
    }
    elseif ($value['type']) {
      $telephone = $this->t('<b>@type:</b> <a href=":tel">@tel</a>', $t_args);
    }
    else {
      $telephone = $this->t('<a href=":tel">@tel</a>', $t_args);
    }
    return ['telephone' => ['#markup' => $telephone]];
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItemValue(array $element, array $value) {
    $t_args = [
      '@tel' => $value['phone'],
      '@ext' => $value['ext'],
      '@type' => $value['type'],
    ];
    if ($value['ext'] && $value['type']) {
      return ['telephone' => $this->t('@type: @tel x@ext', $t_args)];
    }
    elseif ($value['ext']) {
      return ['telephone' => $this->t('@tel x@ext', $t_args)];
    }
    elseif ($value['type']) {
      return ['telephone' => $this->t('@type: @tel', $t_args)];
    }
    else {
      return ['telephone' => $this->t('@tel', $t_args)];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['composite']['phone__international'] = [
      '#title' => $this->t('Enhance support for international phone numbers'),
      '#type' => 'checkbox',
      '#description' => $this->t('Enhance the telephone element\'s international support using the jQuery <a href=":href">International Telephone Input</a> plugin.', [':href' => 'http://intl-tel-input.com/']),
      '#return_value' => TRUE,
      '#access' => $this->librariesManager->isIncluded('jquery.intl-tel-input'),
    ];
    $form['composite']['phone__international_initial_country'] = [
      '#title' => $this->t('Initial country'),
      '#type' => 'select',
      '#empty_option' => '',
      '#options' => [
        'auto' => $this->t('Auto detect'),
      ] + CountryManager::getStandardList(),
      '#states' => [
        'visible' => [
          ':input[name="properties[phone__international]"]' => ['checked' => TRUE],
        ],
      ],
      '#access' => $this->librariesManager->isIncluded('jquery.intl-tel-input'),
    ];
    return $form;
  }

}
