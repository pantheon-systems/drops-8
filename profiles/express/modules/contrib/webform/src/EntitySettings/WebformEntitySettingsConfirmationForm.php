<?php

namespace Drupal\webform\EntitySettings;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform confirmation settings.
 */
class WebformEntitySettingsConfirmationForm extends WebformEntitySettingsBaseForm {

  /**
   * The webform token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * Constructs a WebformEntitySettingsConfirmationForm.
   *
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The webform token manager.
   */
  public function __construct(WebformTokenManagerInterface $token_manager) {
    $this->tokenManager = $token_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('webform.token_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->entity;

    $elements = $webform->getElementsDecoded();
    if (!empty($elements['#method'])) {
      drupal_set_message($this->t('Form is being posted using a custom method. Confirmation page must be handled by the <a href=":href">custom form action</a>.', [':href' => $webform->toUrl('settings-form')->toString()]), 'warning');
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->entity;

    $settings = $webform->getSettings();

    // Confirmation type.
    $form['confirmation_type'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Confirmation type'),
    ];
    $form['confirmation_type']['ajax_confirmation'] = [
      '#type' => 'webform_message',
      '#message_type' => 'warning',
      '#message_message' => $this->t("Only 'Inline' and 'Message' confirmation types are fully supported by Ajax."),
      '#access' => $settings['ajax'],
    ];
    $form['confirmation_type']['confirmation_type'] = [
      '#title' => $this->t('Confirmation type'),
      '#type' => 'radios',
      '#options' => [
        WebformInterface::CONFIRMATION_PAGE => $this->t('Page (redirects to new page and displays the confirmation message)'),
        WebformInterface::CONFIRMATION_INLINE => $this->t('Inline (reloads the current page and replaces the webform with the confirmation message)'),
        WebformInterface::CONFIRMATION_MESSAGE => $this->t('Message (reloads the current page/form and displays the confirmation message at the top of the page)'),
        WebformInterface::CONFIRMATION_MODAL => $this->t('Modal (reloads the current page/form and displays the confirmation message in a modal dialog)'),
        WebformInterface::CONFIRMATION_URL => $this->t('URL (redirects to a custom path or URL)'),
        WebformInterface::CONFIRMATION_URL_MESSAGE => $this->t('URL with message (redirects to a custom path or URL and displays the confirmation message at the top of the page)'),
      ],
      '#default_value' => $settings['confirmation_type'],
    ];

    // Confirmation url.
    $form['confirmation_url'] = [
      '#type' => 'details',
      '#title' => $this->t('Confirmation URL'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          [':input[name="confirmation_type"]' => ['value' => WebformInterface::CONFIRMATION_URL]],
          'or',
          [':input[name="confirmation_type"]' => ['value' => WebformInterface::CONFIRMATION_URL_MESSAGE]],
        ],
      ],
    ];
    $form['confirmation_url']['confirmation_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Confirmation URL'),
      '#description' => $this->t('URL to redirect the user to upon successful submission.'),
      '#default_value' => $settings['confirmation_url'],
      '#maxlength' => NULL,
      '#states' => [
        'required' => [
          [':input[name="confirmation_type"]' => ['value' => WebformInterface::CONFIRMATION_URL]],
          'or',
          [':input[name="confirmation_type"]' => ['value' => WebformInterface::CONFIRMATION_URL_MESSAGE]],
        ],
      ],
    ];

    // Confirmation settings.
    $form['confirmation_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Confirmation settings'),
      '#open' => TRUE,
      '#states' => [
        'invisible' => [
          ':input[name="confirmation_type"]' => ['value' => WebformInterface::CONFIRMATION_URL],
        ],
      ],
    ];
    $form['confirmation_settings']['confirmation_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Confirmation title'),
      '#description' => $this->t('Page title to be shown upon successful submission.'),
      '#default_value' => $settings['confirmation_title'],
      '#states' => [
        'visible' => [
          [':input[name="confirmation_type"]' => ['value' => WebformInterface::CONFIRMATION_PAGE]],
          'or',
          [':input[name="confirmation_type"]' => ['value' => WebformInterface::CONFIRMATION_MODAL]],
        ],
      ],
    ];
    $form['confirmation_settings']['confirmation_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Confirmation message'),
      '#description' => $this->t('Message to be shown upon successful submission.'),
      '#default_value' => $settings['confirmation_message'],
      '#states' => [
        'invisible' => [
          ':input[name="confirmation_type"]' => ['value' => WebformInterface::CONFIRMATION_URL],
        ],
      ],
    ];
    $form['confirmation_settings']['token_tree_link'] = $this->tokenManager->buildTreeLink();

    // Attributes.
    $form['confirmation_attributes_container'] = [
      '#type' => 'details',
      '#title' => $this->t('Confirmation attributes'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          [':input[name="confirmation_type"]' => ['value' => WebformInterface::CONFIRMATION_PAGE]],
          'or',
          [':input[name="confirmation_type"]' => ['value' => WebformInterface::CONFIRMATION_INLINE]],
        ],
      ],
    ];
    $form['confirmation_attributes_container']['confirmation_attributes'] = [
      '#type' => 'webform_element_attributes',
      '#title' => $this->t('Confirmation'),
      '#classes' => $this->config('webform.settings')->get('settings.confirmation_classes'),
      '#default_value' => $settings['confirmation_attributes'],
    ];

    // Back.
    $form['back'] = [
      '#type' => 'details',
      '#title' => $this->t('Confirmation back link'),
      '#states' => [
        'visible' => [
          [':input[name="confirmation_type"]' => ['value' => WebformInterface::CONFIRMATION_PAGE]],
          'or',
          [':input[name="confirmation_type"]' => ['value' => WebformInterface::CONFIRMATION_INLINE]],
        ],
      ],
    ];
    $form['back']['confirmation_back'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display back to webform link'),
      '#return_value' => TRUE,
      '#default_value' => $settings['confirmation_back'],
    ];
    $form['back']['back_container'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          [':input[name="confirmation_back"]' => ['checked' => TRUE]],
        ],
      ],
    ];
    $form['back']['back_container']['confirmation_back_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Confirmation back link label'),
      '#size' => 20,
      '#default_value' => $settings['confirmation_back_label'],
    ];
    $form['back']['back_container']['confirmation_back_attributes_container'] = [
      '#type' => 'details',
      '#title' => $this->t('Confirmation back link attributes'),
    ];
    $form['back']['back_container']['confirmation_back_attributes_container']['confirmation_back_attributes'] = [
      '#type' => 'webform_element_attributes',
      '#title' => $this->t('Confirmation back link'),
      '#classes' => $this->config('webform.settings')->get('settings.confirmation_back_classes'),
      '#default_value' => $settings['confirmation_back_attributes'],
    ];
    $form['back']['back_container']['token_tree_link'] = $this->tokenManager->buildTreeLink();

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    // Set settings.
    $webform->setSettings($values);

    parent::save($form, $form_state);
  }

}
