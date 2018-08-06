<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'datelist' element.
 *
 * @WebformElement(
 *   id = "datelist",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Datetime!Element!Datelist.php/class/Datelist",
 *   label = @Translation("Date list"),
 *   description = @Translation("Provides a form element for date & time selection using select menus and text fields."),
 *   category = @Translation("Date/time elements"),
 * )
 */
class DateList extends DateBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + [
      // Date settings.
      'date_part_order' => [
        'year',
        'month',
        'day',
        'hour',
        'minute',
      ],
      'date_text_parts' => [
        'year',
      ],
      'date_year_range' => '1900:2050',
      'date_increment' => 1,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getElementSelectorInputsOptions(array $element) {
    $date_parts = (isset($element['#date_part_order'])) ? $element['#date_part_order'] : ['year', 'month', 'day', 'hour', 'minute'];

    $t_args = ['@title' => $this->getAdminLabel($element)];
    $selectors = [
      'day' => (string) $this->t('@title days', $t_args),
      'month' => (string) $this->t('@title months', $t_args),
      'year' => (string) $this->t('@title years', $t_args),
      'hour' => (string) $this->t('@title hours', $t_args),
      'minute' => (string) $this->t('@title minutes', $t_args),
      'second' => (string) $this->t('@title seconds', $t_args),
      'ampm' => (string) $this->t('@title am/pm', $t_args),
    ];

    $selectors = array_intersect_key($selectors, array_combine($date_parts, $date_parts));
    foreach ($selectors as &$selector) {
      $selector .= ' [' . $this->t('Select') . ']';
    }
    return $selectors;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['date'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Date list settings'),
    ];
    $form['date']['date_part_order_label'] = [
      '#type' => 'item',
      '#title' => $this->t('Date part and order'),
      '#description' => $this->t("Select the date parts and order that should be used in the element."),
      '#access' => TRUE,
    ];
    $form['date']['date_part_order'] = [
      '#type' => 'webform_tableselect_sort',
      '#header' => ['part' => 'Date part'],
      '#options' => [
        'day' => ['part' => $this->t('Days')],
        'month' => ['part' => $this->t('Months')],
        'year' => ['part' => $this->t('Years')],
        'hour' => ['part' => $this->t('Hours')],
        'minute' => ['part' => $this->t('Minutes')],
        'second' => ['part' => $this->t('Seconds')],
        'ampm' => ['part' => $this->t('AM/PM')],
      ],
    ];
    $form['date']['date_text_parts'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Date text parts'),
      '#description' => $this->t("Select date parts that should be presented as text fields instead of drop-down selectors."),
      '#options' => [
        'day' => $this->t('Days'),
        'month' => $this->t('Months'),
        'year' => $this->t('Years'),
        'hour' => $this->t('Hours'),
        'minute' => $this->t('Minutes'),
        'second' => $this->t('Seconds'),
      ],
    ];
    $form['date']['date_year_range'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Date year range'),
      '#description' => $this->t("A description of the range of years to allow, like '1900:2050', '-3:+3' or '2000:+3', where the first value describes the earliest year and the second the latest year in the range.") . ' ' .
      $this->t('Use min/max validation to define a more specific date range.'),
    ];
    $form['date']['date_increment'] = [
      '#type' => 'number',
      '#title' => $this->t('Date increment'),
      '#description' => $this->t('The increment to use for minutes and seconds'),
      '#min' => 1,
      '#size' => 4,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    $values = $form_state->getValues();
    $values['date_part_order'] = array_values($values['date_part_order']);
    $values['date_text_parts'] = array_values(array_filter($values['date_text_parts']));
    $form_state->setValues($values);
  }

  /**
   * {@inheritdoc}
   */
  protected function setConfigurationFormDefaultValue(array &$form, array &$element_properties, array &$property_element, $property_name) {
    if (in_array($property_name, ['date_text_parts', 'date_part_order'])) {
      $element_properties[$property_name] = array_combine($element_properties[$property_name], $element_properties[$property_name]);
    }
    parent::setConfigurationFormDefaultValue($form, $element_properties, $property_element, $property_name);
  }

}
