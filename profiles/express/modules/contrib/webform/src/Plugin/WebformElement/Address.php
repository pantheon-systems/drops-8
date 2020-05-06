<?php

namespace Drupal\webform\Plugin\WebformElement;

use CommerceGuys\Addressing\AddressFormat\FieldOverride;
use Drupal\address\FieldHelper;
use Drupal\address\LabelHelper;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'address' element.
 *
 * @WebformElement(
 *   id = "address",
 *   label = @Translation("Advanced address"),
 *   description = @Translation("Provides advanced element for storing, validating and displaying international postal addresses."),
 *   category = @Translation("Composite elements"),
 *   composite = TRUE,
 *   multiline = TRUE,
 *   states_wrapper = TRUE,
 *   dependencies = {
 *     "address",
 *   }
 * )
 *
 * @see \Drupal\address\Element\Address
 */
class Address extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $properties = [
      // Element settings.
      'title' => '',
      'default_value' => [],
      // Description/Help.
      'help' => '',
      'help_title' => '',
      'description' => '',
      'more' => '',
      'more_title' => '',
      // Form display.
      'title_display' => 'invisible',
      'description_display' => '',
      'help_display' => '',
      // Form validation.
      'required' => FALSE,
      // Submission display.
      'format' => $this->getItemDefaultFormat(),
      'format_html' => '',
      'format_text' => '',
      'format_items' => $this->getItemsDefaultFormat(),
      'format_items_html' => '',
      'format_items_text' => '',
      // Address settings.
      'available_countries' => [],
      'field_overrides' => [],
      'langcode_override' => '',
    ] + $this->defineDefaultBaseProperties()
      + $this->defineDefaultMultipleProperties();
    unset($properties['multiple__header']);
    return $properties;
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    $element['#theme_wrappers'] = [];

    // #title display defaults to invisible.
    $element += [
      '#title_display' => 'invisible',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareElementValidateCallbacks(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepareElementValidateCallbacks($element, $webform_submission);

    $element['#element_validate'][] = [get_class($this), 'validateAddress'];
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareElementPreRenderCallbacks(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepareElementPreRenderCallbacks($element, $webform_submission);

    // Replace 'form_element' theme wrapper with composite form element.
    // @see \Drupal\Core\Render\Element\PasswordConfirm
    $element['#pre_render'] = [[get_called_class(), 'preRenderWebformCompositeFormElement']];
  }

  /**
   * {@inheritdoc}
   */
  public function getCompositeElements() {
    return [];
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\address\Plugin\Field\FieldType\AddressItem::schema
   */
  public function initializeCompositeElements(array &$element) {
    $element['#webform_composite_elements'] = [
      'given_name' => [
        '#title' => $this->t('Given name'),
        '#type' => 'textfield',
        '#maxlength' => 255,
      ],
      'family_name' => [
        '#title' => $this->t('Family name'),
        '#type' => 'textfield',
        '#maxlength' => 255,
      ],
      'additional_name' => [
        '#title' => $this->t('Additional name'),
        '#type' => 'textfield',
        '#maxlength' => 255,
      ],
      'organization' => [
        '#title' => $this->t('Organization'),
        '#type' => 'textfield',
        '#maxlength' => 255,
      ],
      'address_line1' => [
        '#title' => $this->t('Address line 1'),
        '#type' => 'textfield',
        '#maxlength' => 255,
      ],
      'address_line2' => [
        '#title' => $this->t('Address line 2'),
        '#type' => 'textfield',
        '#maxlength' => 255,
      ],
      'postal_code' => [
        '#title' => $this->t('Postal code'),
        '#type' => 'textfield',
        '#maxlength' => 255,
      ],
      'locality' => [
        '#title' => $this->t('Locality'),
        '#type' => 'textfield',
        '#maxlength' => 255,
      ],
      'dependent_locality' => [
        '#title' => $this->t('Dependent_locality'),
        '#type' => 'textfield',
        '#maxlength' => 255,
      ],
      'administrative_area' => [
        '#title' => $this->t('Administrative area'),
        '#type' => 'textfield',
        '#maxlength' => 255,
      ],
      'country_code' => [
        '#title' => $this->t('Country code'),
        '#type' => 'textfield',
        '#maxlength' => 2,
      ],
      'langcode' => [
        '#title' => $this->t('Language code'),
        '#type' => 'textfield',
        '#maxlength' => 32,
      ],
      'sorting_code' => [
        '#title' => $this->t('Sorting code'),
        '#type' => 'textfield',
        '#maxlength' => 255,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $format = $this->getItemFormat($element);
    if ($format === 'value') {
      return $this->buildAddress($element, $webform_submission, $options);
    }
    else {
      return parent::formatHtmlItem($element, $webform_submission, $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $format = $this->getItemFormat($element);
    if ($format === 'value') {
      $build = $this->buildAddress($element, $webform_submission, $options);
      $html = \Drupal::service('renderer')->renderPlain($build);
      return trim(MailFormatHelper::htmlToText($html));
    }
    else {
      return parent::formatTextItem($element, $webform_submission, $options);
    }
  }

  /**
   * Build formatted address.
   *
   * The below code is copied form the protected
   * AddressDefaultFormatter::viewElements method.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $options
   *   An array of options.
   *
   * @return array
   *   A render array containing the formatted address.
   *
   * @see \Drupal\address\Plugin\Field\FieldFormatter\AddressDefaultFormatter::viewElements
   * @see \Drupal\address\Plugin\Field\FieldFormatter\AddressDefaultFormatter::viewElement
   */
  protected function buildAddress(array $element, WebformSubmissionInterface $webform_submission, array $options) {
    /** @var \CommerceGuys\Addressing\AddressFormat\AddressFormatRepositoryInterface $address_format_repository */
    $address_format_repository = \Drupal::service('address.address_format_repository');
    /** @var \CommerceGuys\Addressing\Country\CountryRepositoryInterface $country_repository */
    $country_repository = \Drupal::service('address.country_repository');

    $value = $this->getValue($element, $webform_submission, $options);
    // Skip if value or country code is empty.
    if (empty($value) || empty($value['country_code'])) {
      return [];
    }

    // @see \Drupal\address\Plugin\Field\FieldFormatter\AddressDefaultFormatter::viewElements
    $build = [
      '#prefix' => '<div class="address" translate="no">',
      '#suffix' => '</div>',
      '#post_render' => [
        ['\Drupal\address\Plugin\Field\FieldFormatter\AddressDefaultFormatter', 'postRender'],
      ],
      '#cache' => [
        'contexts' => [
          'languages:' . LanguageInterface::TYPE_INTERFACE,
        ],
      ],
    ];

    // @see \Drupal\address\Plugin\Field\FieldFormatter\AddressDefaultFormatter::viewElement
    $country_code = $value['country_code'];
    $countries = $country_repository->getList();
    $address_format = $address_format_repository->get($country_code);

    $build += [
      '#address_format' => $address_format,
      '#locale' => 'und',
    ];
    $build['country'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#attributes' => ['class' => ['country']],
      '#value' => Html::escape($countries[$country_code]),
      '#placeholder' => '%country',
    ];
    foreach ($address_format->getUsedFields() as $field) {
      $property = FieldHelper::getPropertyName($field);
      $class = str_replace('_', '-', $property);
      $build[$property] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => ['class' => [$class]],
        '#value' => (!empty($value[$property])) ? Html::escape($value[$property]) : '',
        '#placeholder' => '%' . $field,
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Get field overrider from element properties.
    $element_properties = $form_state->get('element_properties');
    $field_overrides = $element_properties['field_overrides'];
    unset($element_properties['field_overrides']);
    $form_state->set('element_properties', $element_properties);

    $form['address'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Address settings'),
    ];

    /**************************************************************************/
    // Copied from: \Drupal\address\Plugin\Field\FieldType\AddressItem::fieldSettingsForm
    /**************************************************************************/

    $languages = \Drupal::languageManager()->getLanguages(LanguageInterface::STATE_ALL);
    $language_options = [];
    foreach ($languages as $langcode => $language) {
      if (!$language->isLocked()) {
        $language_options[$langcode] = $language->getName();
      }
    }

    $form['address']['available_countries'] = [
      '#type' => 'select',
      '#title' => $this->t('Available countries'),
      '#description' => $this->t('If no countries are selected, all countries will be available.'),
      '#options' => \Drupal::service('address.country_repository')->getList(),
      '#multiple' => TRUE,
      '#size' => 10,
      '#select2' => TRUE,
    ];
    WebformElementHelper::process($form['address']['available_countries']);
    $form['address']['langcode_override'] = [
      '#type' => 'select',
      '#title' => $this->t('Language override'),
      '#description' => $this->t('Ensures entered addresses are always formatted in the same language.'),
      '#options' => $language_options,
      '#empty_option' => $this->t('- No override -'),
      '#access' => \Drupal::languageManager()->isMultilingual(),
    ];
    $form['address']['field_overrides_title'] = [
      '#type' => 'item',
      '#title' => $this->t('Field overrides'),
      '#description' => $this->t('Use field overrides to override the country-specific address format, forcing specific fields to always be hidden, optional, or required.'),
      '#access' => TRUE,
    ];
    $form['address']['field_overrides'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Field'),
        $this->t('Override'),
      ],
      '#access' => TRUE,
    ];
    foreach (LabelHelper::getGenericFieldLabels() as $field_name => $label) {
      $override = isset($field_overrides[$field_name]) ? $field_overrides[$field_name] : '';
      $form['address']['field_overrides'][$field_name] = [
        '#access' => TRUE,
        'field_label' => [
          '#access' => TRUE,
          '#type' => 'markup',
          '#markup' => $label,
        ],
        'override' => [
          '#access' => TRUE,
          '#type' => 'select',
          '#default_value' => $override,
          '#options' => [
            FieldOverride::HIDDEN => $this->t('Hidden'),
            FieldOverride::OPTIONAL => $this->t('Optional'),
            FieldOverride::REQUIRED => $this->t('Required'),
          ],
          '#empty_option' => $this->t('- No override -'),
          '#parents' => ['properties', 'field_overrides', $field_name],
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $values['field_overrides'] = array_filter($values['field_overrides']);
    $form_state->setValues($values);
    parent::validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    return [
      [
        'given_name' => 'John',
        'family_name' => 'Smith',
        'organization' => 'Google Inc.',
        'address_line1' => '1098 Alta Ave',
        'postal_code' => '94043',
        'locality' => 'Mountain View',
        'administrative_area' => 'CA',
        'country_code' => 'US',
        'langcode' => 'en',
      ],
    ];
  }

  /**
   * Form API callback. Make sure address element value includes a country code.
   */
  public static function validateAddress(array &$element, FormStateInterface $form_state, array &$completed_form) {
    $value = $element['#value'];
    if (empty($element['#multiple'])) {
      if (empty($value['country_code'])) {
        $form_state->setValueForElement($element, NULL);
      }
    }
    else {
      foreach ($value as $index => $item) {
        if (empty($item['country_code'])) {
          unset($value[$index]);
        }
      }
      $value = array_values($value);
      $form_state->setValueForElement($element, $value ?: NULL);
    }
  }

}
