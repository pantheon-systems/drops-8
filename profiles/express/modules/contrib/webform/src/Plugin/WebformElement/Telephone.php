<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Locale\CountryManager;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'tel' element.
 *
 * @WebformElement(
 *   id = "tel",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Tel.php/class/Tel",
 *   label = @Translation("Telephone"),
 *   description = @Translation("Provides a form element for entering a telephone number."),
 *   category = @Translation("Advanced elements"),
 * )
 */
class Telephone extends TextBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return [
      'multiple' => FALSE,
      'international' => FALSE,
      'international_initial_country' => '',
    ] + parent::getDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    // Add international library and classes.
    if (!empty($element['#international']) && $this->librariesManager->isIncluded('jquery.intl-tel-input')) {
      $element['#attached']['library'][] = 'webform/webform.element.telephone';
      $element['#attributes']['class'][] = 'js-webform-telephone-international';
      $element['#attributes']['class'][] = 'webform-webform-telephone-international';
      if (!empty($element['#international_initial_country'])) {
        $element['#attributes']['data-webform-telephone-international-initial-country'] = $element['#international_initial_country'];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['telephone'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Telephone settings'),
    ];
    $form['telephone']['international'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enhance support for international phone numbers'),
      '#description' => $this->t('Enhance the telephone element\'s international support using the jQuery <a href=":href">International Telephone Input</a> plugin.', [':href' => 'http://intl-tel-input.com/']),
      '#return_value' => TRUE,
    ];
    $form['telephone']['international_initial_country'] = [
      '#title' => $this->t('Initial country'),
      '#type' => 'select',
      '#empty_option' => '',
      '#options' => [
        'auto' => $this->t('Auto detect'),
      ] + CountryManager::getStandardList(),
      '#states' => [
        'visible' => [':input[name="properties[international]"]' => ['checked' => TRUE]],
      ],
    ];
    if ($this->librariesManager->isExcluded('jquery.intl-tel-input')) {
      $form['telephone']['#access'] = FALSE;
      $form['telephone']['international']['#access'] = FALSE;
      $form['telephone']['international_initial_country']['#access'] = FALSE;
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    if (empty($value)) {
      return '';
    }

    $format = $this->getItemFormat($element);
    switch ($format) {
      case 'link':
        /**********************************************************************/
        // Issue #2484693: Telephone Link field formatter breaks Drupal with 5
        // digits or less in the number
        // return [
        //   '#type' => 'link',
        //   '#title' => $value,
        //   '#url' => \Drupal::pathValidator()->getUrlIfValid('tel:' . $value),
        // ];
        // Workaround: Manually build a static HTML link.
        /**********************************************************************/

        $t_args = [':tel' => 'tel:' . $value, '@tel' => $value];
        return t('<a href=":tel">@tel</a>', $t_args);

      default:
        return parent::formatHtmlItem($element, $webform_submission, $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefaultFormat() {
    return 'link';
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormats() {
    return parent::getItemFormats() + [
      'link' => $this->t('Link'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return parent::preview() + [
      '#international' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    if (empty($element['#international'])) {
      return FALSE;
    }
    return [
      '+1 212-333-4444',
      '+1 718-555-6666',
    ];
  }

}
