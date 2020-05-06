<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformActions as WebformActionsElement;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'webform_actions' element.
 *
 * @WebformElement(
 *   id = "webform_actions",
 *   default_key = "actions",
 *   label = @Translation("Submit button(s)"),
 *   description = @Translation("Provides an element that contains a Webform's submit, draft, wizard, and/or preview buttons."),
 *   category = @Translation("Buttons"),
 * )
 */
class WebformActions extends ContainerBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $properties = [
      // Title.
      'title' => '',
      // Attributes.
      'attributes' => [],
    ] + $this->defineDefaultBaseProperties();
    foreach (WebformActionsElement::$buttons as $button) {
      $properties[$button . '_hide'] = FALSE;
      $properties[$button . '__label'] = '';
      $properties[$button . '__attributes'] = [];
    }
    return $properties;
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function isInput(array $element) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isContainer(array $element) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isRoot() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function build($format, array &$element, WebformSubmissionInterface $webform_submission, array $options = []) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefaultFormat() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormats() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    // Containers should never have values and therefore should never have
    // a test value.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $form_state->getFormObject()->getWebform();

    $form['actions'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Buttons'),
    ];
    $draft_enabled = ($webform->getSetting('draft') != WebformInterface::DRAFT_NONE);
    $reset_enabled = $webform->getSetting('form_reset');
    $wizard_enabled = $webform->hasWizardPages();
    $preview_enabled = ($webform->getSetting('preview') != DRUPAL_DISABLED);

    $buttons = [
      'submit' => [
        'title' => $this->t('Submit'),
        'label' => $this->t('submit'),
        'access' => TRUE,
      ],
      'reset' => [
        'title' => $this->t('Reset'),
        'label' => $this->t('reset'),
        'access' => $reset_enabled,
      ],
      'draft' => [
        'title' => $this->t('Draft'),
        'label' => $this->t('draft'),
        'access' => $draft_enabled,
      ],
      'update' => [
        'title' => $this->t('Update'),
        'label' => $this->t('Update'),
        'description' => $this->t('This is used after a submission has been saved and finalized to the database.'),
        'access' => !$webform->isResultsDisabled(),
      ],
      'wizard_prev' => [
        'title' => $this->t('Wizard previous'),
        'label' => $this->t('wizard previous'),
        'description' => $this->t('This is used for the previous page button within a wizard.'),
        'access' => $wizard_enabled,
      ],
      'wizard_next' => [
        'title' => $this->t('Wizard next'),
        'label' => $this->t('wizard next'),
        'description' => $this->t('This is used for the next page button within a wizard.'),
        'access' => $wizard_enabled,
      ],
      'preview_prev' => [
        'title' => $this->t('Preview previous'),
        'label' => $this->t('preview previous'),
        'description' => $this->t('The text for the button to go backwards from the preview page.'),
        'access' => $preview_enabled,
      ],
      'preview_next' => [
        'title' => $this->t('Preview next'),
        'label' => $this->t('preview next'),
        'description' => $this->t('The text for the button that will proceed to the preview page.'),
        'access' => $preview_enabled,
      ],
    ];

    foreach ($buttons as $name => $button) {
      $t_args = [
        '@title' => $button['title'],
        '@label' => $button['label'],
        '%label' => $button['label'],
      ];

      $form[$name . '_settings'] = [
        '#type' => 'details',
        '#open' => TRUE,
        '#weight' => -10,
        '#title' => $this->t('@title button', $t_args),
        '#access' => $button['access'],
      ];
      if (!empty($button['description'])) {
        $form[$name . '_settings']['description'] = [
          '#markup' => $button['description'],
          '#access' => TRUE,
        ];
      }
      $form[$name . '_settings'][$name . '_hide'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Hide @label button', $t_args),
        '#return_value' => TRUE,
      ];
      if (strpos($name, '_prev') === FALSE && $name !== 'reset') {
        $form[$name . '_settings'][$name . '_hide_message'] = [
          '#type' => 'webform_message',
          '#access' => TRUE,
          '#message_message' => $this->t("Hiding the %label button can cause unexpected issues, please make sure to include the %label button using another 'Submit button(s)' element.", $t_args),
          '#message_type' => 'warning',
          '#states' => [
            'visible' => [':input[name="properties[' . $name . '_hide]"]' => ['checked' => TRUE]],
          ],
        ];
      }
      $form[$name . '_settings'][$name . '__label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('@title button label', $t_args),
        '#description' => $this->t('Defaults to: %value', ['%value' => $this->configFactory->get('webform.settings')->get('settings.default_' . $name . '_button_label')]),
        '#size' => 20,
        '#attributes' => [
          // Make sure default value is never cleared by #states API.
          // @see js/webform.states.js
          'data-webform-states-no-clear' => TRUE,
        ],
        '#states' => [
          'visible' => [':input[name="properties[' . $name . '_hide]"]' => ['checked' => FALSE]],
        ],
      ];
      $form[$name . '_settings'][$name . '__attributes'] = [
        '#type' => 'webform_element_attributes',
        '#title' => $this->t('@title button', $t_args),
        '#classes' => $this->configFactory->get('webform.settings')->get('settings.button_classes'),
        '#states' => [
          'visible' => [':input[name="properties[' . $name . '_hide]"]' => ['checked' => FALSE]],
        ],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\webform_ui\Form\WebformUiElementEditForm $form_object */
    $form_object = $form_state->getFormObject();

    if (!$form_object->getWebform()->hasActions()) {
      $form['element']['title']['#default_value'] = (string) $this->t('Submit button(s)');
    }

    // Hide element settings for default 'actions' to prevent UX confusion.
    $key = $form_object->getKey() ?: $form_object->getDefaultKey();
    if ($key === 'actions') {
      $form['element']['#access'] = FALSE;
    }

    return $form;
  }

}
