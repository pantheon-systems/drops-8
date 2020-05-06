<?php

namespace Drupal\webform_options_custom\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformHtmlEditor;
use Drupal\webform\Element\WebformMessage;
use Drupal\webform\Entity\WebformOptions;
use Drupal\webform\Plugin\WebformElement\Select;
use Drupal\webform\Plugin\WebformElementAssetInterface;
use Drupal\webform\Utility\WebformOptionsHelper;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform_options_custom\Plugin\WebformOptionsCustomInterface;

/**
 * Provides a custom options element.
 *
 * @WebformElement(
 *   id = "webform_options_custom",
 *   label = @Translation("Custom element"),
 *   description = @Translation("Provides a form element for creating custom options using HTML and SVG markup."),
 *   category = @Translation("Custom elements"),
 *   deriver = "Drupal\webform_options_custom\Plugin\Derivative\WebformOptionsCustomDeriver"
 * )
 */
class WebformOptionsCustom extends Select implements WebformOptionsCustomInterface, WebformElementAssetInterface {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $properties = [
      // Options settings.
      'multiple' => FALSE,
      'multiple_error' => '',
      'empty_option' => '',
      'empty_value' => '',
      'select2' => FALSE,
      'choices' => FALSE,
      'chosen' => FALSE,
      'placeholder' => '',
      'help_display' => '',
      'size' => '',
      'options_custom' => '',
      'options_description_display' => TRUE,
    ] + parent::defineDefaultProperties();
    unset($properties['options_randomize']);
    return $properties;
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function supportsMultipleValues() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function initialize(array &$element) {
    // Make sure the #template property is not set by the element
    // since it allows for unfiltered HTML, CSS, and JS.
    unset($element['#template']);
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    // Remove the #template property to make sure people can't inject
    // custom markup.
    $this->setOptions($element, ['webform_submission' => $webform_submission]);
    parent::prepare($element, $webform_submission);
  }

  /**
   * {@inheritdoc}
   */
  protected function getElementSelectorInputsOptions(array $element) {
    $this->setOptions($element);
    $title = $this->getAdminLabel($element);
    return ['select' => $title . ' [' . $this->t('Select') . ']'];
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorSourceValues(array $element) {
    $this->setOptions($element);
    return parent::getElementSelectorSourceValues($element);
  }

  /**
   * Build an element as text or HTML.
   *
   * @param string $format
   *   Format of the element, text or html.
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $options
   *   An array of options.
   *
   * @return array
   *   A render array representing an element as text or HTML.
   */
  protected function build($format, array &$element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $this->setOptions($element, ['webform_submission' => $webform_submission]);
    return parent::build($format, $element, $webform_submission, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    list(, $webform_options_custom_id) = explode(':', $this->getPluginId());
    /** @var \Drupal\webform_options_custom\WebformOptionsCustomInterface $webform_options_custom */
    $webform_options_custom = $this->entityTypeManager->getStorage('webform_options_custom')->load($webform_options_custom_id);
    $element = $webform_options_custom->getElement();
    $this->setOptions($element);
    $rows = [];
    foreach ($element['#options'] as $value => $text) {
      $rows[] = [
        $value,
        ['data' => ['#markup' => str_replace(WebformOptionsHelper::DESCRIPTION_DELIMITER, '<br/>', $text)]],
      ];
    }

    // If the custom options are defined, then the options element
    // is not required.
    if ($this->hasProperty('options')) {
      $form['options']['options']['#options_description'] = TRUE;

      if ($this->getEntity()->getOptions() || $this->getEntity()->getTemplateOptions()) {
        $form['options']['options']['#type'] = 'webform_options';
        $form['options']['options']['#required'] = FALSE;
        $form['options']['options_message'] = [
          '#type' => 'webform_message',
          '#message_type' => 'info',
          '#message_message' => $this->t('Below options are used to enhance and also translate the custom options.'),
          '#message_close' => TRUE,
          '#message_storage' => WebformMessage::STORAGE_SESSION,
          '#access' => TRUE,
          '#weight' => -20,
        ];
      }
    }

    if ($rows) {
      $form['options']['custom_options'] = [
        '#type' => 'details',
        '#title' => $this->t('Custom options'),
        'table' => [
          '#type' => 'table',
          '#header' => [
            $this->t('Option value'),
            $this->t('Option text / description'),
          ],
          '#rows' => $rows,
          '#access' => TRUE,
        ],
        '#access' => TRUE,
        '#weight' => -20,
      ];
    }

    // Set help to the description.
    $form['options']['#description'] = WebformHtmlEditor::checkMarkup($webform_options_custom->get('help'));

    // Do not allows options disable to be altered since this handled
    // via tooltips.
    unset($form['options']['options_display_container']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    list(, $webform_options_custom_id) = explode(':', $this->getPluginId());

    /** @var \Drupal\webform_options_custom\WebformOptionsCustomInterface $webform_options_custom */
    $webform_options_custom = $this->entityTypeManager->getStorage('webform_options_custom')->load($webform_options_custom_id);
    return $webform_options_custom->getPreview() + [
      '#title' => $webform_options_custom->label(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    $this->setOptions($element);
    return array_keys($element['#options']);
  }

  /**
   * {@inheritdoc}
   */
  public function hasAssets() {
    return ($this->getCss() || $this->getJavaScript()) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getAssetId() {
    return $this->getEntity()->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getCss() {
    return $this->getEntity()->get('css');
  }

  /**
   * {@inheritdoc}
   */
  public function getJavaScript() {
    return $this->getEntity()->get('javascript');
  }

  /**
   * Get element custom options.
   *
   * @param array $element
   *   An element.
   * @param array $settings
   *   An array of settings used to limit and randomize options.
   */
  protected function setOptions(array &$element, array $settings = []) {
    // Set element options.
    if (!empty($element['#options'])) {
      $element['#options'] = WebformOptions::getElementOptions($element);
    }

    // Set element custom template options.
    list($type, $options_custom) = explode(':', $this->getPluginId());
    $element['#type'] = $type;
    $element['#options_custom'] = $options_custom;

    /** @var \Drupal\webform_options_custom\Element\WebformOptionsCustomInterface $class */
    $class = $this->getFormElementClassDefinition();
    $class::setTemplateOptions($element);
  }

  /**
   * Get webform custom options entity.
   *
   * @return \Drupal\webform_options_custom\WebformOptionsCustomInterface
   *   A webform custom options entity.
   */
  protected function getEntity() {
    list(, $webform_options_custom_id) = explode(':', $this->getPluginId());
    return $this->entityTypeManager->getStorage('webform_options_custom')->load($webform_options_custom_id);
  }

}
