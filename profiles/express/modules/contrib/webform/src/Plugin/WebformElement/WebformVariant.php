<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\Plugin\WebformElementVariantInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\Plugin\WebformElementDisplayOnInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'webform_variant' element.
 *
 * @WebformElement(
 *   id = "webform_variant",
 *   label = @Translation("Variant [EXPERIMENTAL]"),
 *   description = @Translation("Provides a form element for enabling and tracking webform variants."),
 *   category = @Translation("Advanced elements"),
 * )
 */
class WebformVariant extends WebformElementBase implements WebformElementDisplayOnInterface, WebformElementVariantInterface {

  use WebformDisplayOnTrait;

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $properties = [
      // Element settings.
      'title' => '',
      'default_value' => '',
      // Description/Help.
      'help' => '',
      'help_title' => '',
      'description' => '',
      'more' => '',
      'more_title' => '',
      // Form display.
      'title_display' => '',
      'description_display' => '',
      'help_display' => '',
      // Attributes.
      'wrapper_attributes' => [],
      'label_attributes' => [],
      'attributes' => [],
      // Submission display.
      'format' => $this->getItemDefaultFormat(),
      'format_html' => '',
      'format_text' => '',
      // Flexbox.
      'flex' => 1,
      // Variant.
      'variant' => '',
      'prepopulate' => TRUE,
      'randomize' => FALSE,
      'display_on' => static::DISPLAY_ON_NONE,
    ] + $this->defineDefaultBaseProperties();
    unset(
      $properties['states'],
      $properties['states_clear']
    );
    return $properties;
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function isHidden() {
    return !\Drupal::currentUser()->hasPermission('edit webform variants');
  }

  /**
   * {@inheritdoc}
   */
  public function initialize(array &$element) {
    if (!isset($element['#prepopulate'])) {
      $element['#prepopulate'] = TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    // Hide element if it should not be displayed on 'form'.
    if ($this->hasProperty('display_on') && !$this->isDisplayOn($element, static::DISPLAY_ON_FORM)) {
      $element['#access'] = FALSE;
    }

    // Hide empty element.
    if (empty($element['#default_value'])) {
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
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform_ui\Form\WebformUiElementFormBase $form_object */
    $form_object = $form_state->getFormObject();
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $form_state->getFormObject()->getWebform();

    $form = parent::form($form, $form_state);

    /** @var \Drupal\webform\Plugin\WebformVariantManagerInterface $variant_manager */
    $variant_manager = \Drupal::service('plugin.manager.webform.variant');
    $definitions = $variant_manager->getDefinitions();
    $definitions = $variant_manager->getSortedDefinitions($definitions);
    $definitions = $variant_manager->removeExcludeDefinitions($definitions);

    $options = [];
    foreach ($definitions as $plugin_id => $definition) {
      /** @var \Drupal\webform\Plugin\WebformVariantInterface $variant_plugin */
      $variant_plugin = $variant_manager->createInstance($plugin_id);
      if ($variant_plugin->isApplicable($webform)) {
        $options[$plugin_id] = $definition['label'];
      }
    }

    $form['variant'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Variant settings'),
    ];
    if ($this->currentUser->hasPermission('edit webform variants')) {
      if (empty($webform->getElementsVariant())) {
        $form['variant']['variant_message'] = [
          '#type' => 'webform_message',
          '#message_message' => $this->t("After clicking 'Save', the 'Variants' manage tab will be displayed. Use the 'Variants' manage tab to add and remove variants."),
          '#message_type' => 'info',
          '#access' => TRUE,
        ];
      }
      else {
        $t_args = ['@href' => $webform->toUrl('variants')->toString()];
        $form['variant']['variant_message'] = [
          '#type' => 'webform_message',
          '#message_message' => $this->t('Add and remove variants using the <a href="@href">Variants</a> manage tab.', $t_args),
          '#message_type' => 'info',
          '#access' => TRUE,
        ];
      }
    }
    $form['variant']['variant'] = [
      '#type' => 'select',
      '#title' => $this->t('Variant type'),
      '#description' => $this->t("Select the variant type to be displayed on the 'Variants' manage tab."),
      '#options' => $options,
      '#required' => TRUE,
    ];
    // Disable variant type if variants have been created.
    $key = $form_object->getKey();
    $element = $form_object->getElement();
    if ($key
      && isset($element['#variant'])
      && isset($options[$element['#variant']])
      && $webform->getVariants(NULL, NULL, $key)->count()) {
      $form['variant']['variant']['#access'] = FALSE;
      $form['variant']['variant_item'] = [
        '#type' => 'item',
        '#title' => $this->t('Variant type'),
        '#description' => $this->t('This variant is currently in-use. The variant type cannot be changed.'),
        '#markup' => $options[$element['#variant']],
        '#access' => TRUE,
      ];
    }

    $form['variant']['display_on'] = [
      '#type' => 'select',
      '#title' => $this->t('Display on'),
      '#options' => $this->getDisplayOnOptions(TRUE),
      '#required' => TRUE,
    ];
    $form['variant']['prepopulate'] = $form['form']['prepopulate'];
    $form['variant']['prepopulate']['#title'] = $this->t('Prepopulate this variant');
    $form['variant']['prepopulate']['#description'] .= ' ' . $this->t('If checked, variants will be availalbe using query string parameters.');
    unset(
      $form['form']['prepopulate'],
      $form['variant']['prepopulate']['#weight']
    );
    $form['variant']['randomize'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Randomly load variant'),
      '#description' => $this->t("Randomly redirects to the webform with the variant populated using query string parameter. This element's default value/data will be ignored."),
      '#return_value' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="properties[prepopulate]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $display_on_form_states = [
      'visible' => [
        [':input[name="properties[display_on]"]' => ['value' => static::DISPLAY_ON_FORM]],
        'or',
       [':input[name="properties[display_on]"]' => ['value' => static::DISPLAY_ON_BOTH]],
      ],
    ];
    $form['element_description']['#states'] = $display_on_form_states;
    $form['form']['#states'] = $display_on_form_states;
    $form['wrapper_attributes']['#states'] = $display_on_form_states;
    $form['element_attributes']['#states'] = $display_on_form_states;
    $form['label_attributes']['#states'] = $display_on_form_states;

    $display_on_view_states = [
      'visible' => [
        [':input[name="properties[display_on]"]' => ['value' => static::DISPLAY_ON_VIEW]],
        'or',
       [':input[name="properties[display_on]"]' => ['value' => static::DISPLAY_ON_BOTH]],
      ],
    ];
    $form['display']['#states'] = $display_on_view_states;

    $form['#after_build'][] = [get_class($this), 'afterBuild'];

    return $form;
  }

  /**
   * After build handler for variant element.
   */
  public static function afterBuild(array $form, FormStateInterface $form_state) {
    // Remove 'Set default value' actions, since the variant element
    // is not editable.
    unset($form['default']['actions']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorOptions(array $element) {
    return [];
  }

}
