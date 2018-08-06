<?php

namespace Drupal\webform\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformThirdPartySettingsManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure webform third party settings for this site.
 */
class WebformAdminThirdPartySettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_admin_third_party_settings_form';
  }

  /**
   * The webform third party settings manager.
   *
   * @var \Drupal\webform\WebformThirdPartySettingsManagerInterface
   */
  protected $thirdPartySettingsManager;

  /**
   * Constructs a WebformAdminThirdPartySettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\webform\WebformThirdPartySettingsManagerInterface $third_party_settings_manager
   *   The webform third party settings manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, WebformThirdPartySettingsManagerInterface $third_party_settings_manager) {
    parent::__construct($config_factory);
    $this->thirdPartySettingsManager = $third_party_settings_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('webform.third_party_settings_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['webform.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = $this->thirdPartySettingsManager->buildForm($form, $form_state);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('webform.settings');
    $third_party_settings = $form_state->getValue('third_party_settings') + ($config->get('third_party_settings') ?: []);
    $config->set('third_party_settings', $third_party_settings);
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
