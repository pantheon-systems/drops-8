<?php

namespace Drupal\user_external_invite\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InviteSettingsForm extends ConfigFormBase {

  /**
   * InviteSettingsForm constructor.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }


  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_external_invite_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['user_external_invite.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('user_external_invite.settings');

    // Get current user roles for populating form elements.
    $roles = user_role_names();

    $form['user_external_invite_roles'] = [
      '#type' => 'select',
      '#title' => t('Roles users can be invited to join'),
      '#description' => t('Users with permission to send invites will be able to invite users to join a site with any of these roles.  GRANT WITH CAUTION!'),
      '#options' => $roles,
      '#default_value' => $config->get('user_external_invite_roles'),
      '#multiple' => TRUE,
    ];

    $form['user_external_invite_default_role'] = [
      '#title' => t('Default Role to Invite'),
      '#description' => t('Choose the default role you wish to have selected on the invite page.'),
      '#type' => 'radios',
      '#options' => $roles,
      '#default_value' => $config->get('user_external_invite_default_role'),
    ];

    // Days invite valid for.
    $form['user_external_invite_days_valid_for'] = [
      '#type' => 'number',
      '#title' => t('Number of days invites are valid'),
      '#description' => t("Invites are set to expire so many days after they are created. If a user hasn't accepted the invite by that time, then you will have to send a new invite to grant that user a role."),
      '#default_value' => $config->get('user_external_invite_days_valid_for'),
      '#min' => 1,
      '#step' => 1,
    ];

    // Delete old invites after a certain time.
    $form['user_external_invite_delete_old_invites'] = [
      '#type' => 'textfield',
      '#title' => t('Invite Deletion'),
      '#description' => t("Invites are deleted during a cron run after they have passed their expire time. Defaults to 30 days (2592000 seconds)."),
      '#default_value' => $config->get('user_external_invite_delete_old_invites'),
      '#size' => 60,
      '#element_validate' => [[$this, 'elementValidateNumber']],
      '#required' => TRUE,
    ];

    $form['user_external_invite_no_query_params'] = [
      '#type' => 'checkbox',
      '#title' => t('Ignore Query Parameters For Login Link'),
      '#description' => t('Some external authentication systems might not pass back the token and email needed to grant an invite. 
      If checked, the user\'s local account email will be used in evaluating whether to grant a role. This method is less secure so only use if needed.
      This check also assumes that the local account email has been set either before or during external authentication.'),
      '#default_value' => $config->get('user_external_invite_no_query_params'),
    ];

    // From email address.
    $form['user_external_invite_use_universal_from_email'] = [
      '#type' => 'checkbox',
      '#title' => t('Send all invites from a single email address'),
      '#description' => t('If this is not configured, invites will be sent using the email address of the user sending the invite.'),
      '#default_value' => $config->get('user_external_invite_use_universal_from_email'),
    ];

    // Can use the new email field or text field with custom validation.
    $form['user_external_invite_universal_from_email'] = [
      '#type' => 'email',
      //'#type' => 'textfield',
      '#title' => t('Email address invites are sent from'),
      '#default_value' => $config->get('user_external_invite_universal_from_email'),
      //'#maxlength' => 256,
      '#states' => [
        'visible' => [
          ':input[name="user_external_invite_use_universal_from_email"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="user_external_invite_use_universal_from_email"]' => ['checked' => TRUE],
        ],
      ],
      //'#element_validate' => [[$this, 'elementValidateEmail']],
    ];

    // Inviter email templates.
    $form['inviter_template'] = [
      '#type' => 'fieldset',
      '#title' => t('Inviter Templates'),
      '#description' => t('Templates to notify inviter.'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $form['inviter_template']['user_external_invite_confirmation_template'] = [
      '#title' => t('Invitation Confirmation'),
      '#type' => 'textarea',
      '#cols' => 40,
      '#rows' => 5,
      '#default_value' => $config->get('user_external_invite_confirmation_template'),
      '#description' => t('Confirmation message sent to inviter confirming the invitation was sent.'),
    ];

    $form['inviter_template']['user_external_invite_accepted_template'] = [
      '#title' => t('Invitation Accepted Email Template'),
      '#type' => 'textarea',
      '#cols' => 40,
      '#rows' => 5,
      '#default_value' => $config->get('user_external_invite_accepted_template'),
      '#description' => t('Message sent to inviter when the invitee accepts an invite.'),
    ];

    // Invitee email templates.
    $form['invitee_template'] = [
      '#type' => 'fieldset',
      '#title' => t('Invitee Templates'),
      '#description' => t('Templates to notify invitee.'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $form['invitee_template']['user_external_invite_invite_template'] = [
      '#title' => t('Invitation Email Template'),
      '#type' => 'textarea',
      '#cols' => 40,
      '#rows' => 5,
      '#default_value' => $config->get('user_external_invite_invite_template'),
      '#description' => t('Message sent to user being invited.'),
    ];

    $form['invitee_template']['user_external_invite_accepted_confirmation_template'] = [
      '#title' => t('Invitation Accepted Confirmation Email Template'),
      '#type' => 'textarea',
      '#cols' => 40,
      '#rows' => 5,
      '#default_value' => $config->get('user_external_invite_accepted_confirmation_template'),
      '#description' => t('Message sent to invitee confirming the process was completed.'),
    ];

    // @TODO: add warning email about expiring invitations.
    $form['token_help']['content'] = [
      '#type' => 'markup',
      '#token_types' => 'all',
      '#theme' => 'token_tree',
    ];

    // Submit button.
    /*
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
    ];
    */

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('user_external_invite.settings');
    $values = $form_state->getValues();

    // Loop through and save form values.
    foreach ($values as $key => $value) {
      // Since there are non-user input values, we need to check for the prefix
      // added to all variables to filter out values we don't want to save.
      if (strpos($key, 'user_external_invite') !== FALSE) {
        $config->set($key, $value);
      }
    }

    // Save form values to be loaded as defaults.
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Validation handler to check for a positive integer on a field.
   *
   * @param $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $complete_form
   */
  public function elementValidateNumber(&$element, FormStateInterface $form_state, &$complete_form) {
    $element_name = $element['#title']->getUntranslatedString();
    $value = $form_state->getValue($element['#name']);
    if ($value !== '' && (!is_numeric($value) || intval($value) != $value || $value <= 0)) {
      $form_state->setErrorByName($element['#name'], $this->t('@element needs to be a positive integer.', ['@element' => $element_name]));
    }
  }

  /**
   * Validation handler to check that a string is a valid email address.
   *
   * @param $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $complete_form
   */
  public function elementValidateEmail(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = $form_state->getValue($element['#name']);
    if ($value !== '' && !\Drupal::service('email.validator')->isValid($value)) {
      $form_state->setErrorByName($element['#name'], $this->t('@mail needs to be a valid email address.', ['@mail' => $value]));
    }
  }
}
