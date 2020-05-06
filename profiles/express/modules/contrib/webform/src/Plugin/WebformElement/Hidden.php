<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\webform\WebformInterface;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'hidden' element.
 *
 * @WebformElement(
 *   id = "hidden",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Hidden.php/class/Hidden",
 *   label = @Translation("Hidden"),
 *   description = @Translation("Provides a form element for an HTML 'hidden' input element."),
 *   category = @Translation("Basic elements"),
 * )
 */
class Hidden extends TextBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    // Include only the access-view-related base properties.
    $access_properties = $this->defineDefaultBaseProperties();
    $access_properties = array_filter($access_properties, function ($access_default, $access_key) {
      return strpos($access_key, 'access_') === 0;
    }, ARRAY_FILTER_USE_BOTH);

    return [
      // Element settings.
      'title' => '',
      'default_value' => '',
      // Administration.
      'prepopulate' => FALSE,
      'private' => FALSE,
    ] + $access_properties;
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    // Hidden elements should never get a test value.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Remove the default section under the advanced tab.
    unset($form['default']);

    // Add the default value textarea to the element's main settings.
    $form['element']['default_value'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Default value'),
      '#description' => $this->t('The default value of the webform element.'),
      '#maxlength' => NULL,
    ];

    return $form;
  }

}
