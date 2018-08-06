<?php

namespace Drupal\webform;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a webform to configure third party settings.
 */
class WebformEntityThirdPartySettingsForm extends EntityForm {

  /**
   * The third party settings manager.
   *
   * @var \Drupal\webform\WebformThirdPartySettingsManagerInterface
   */
  protected $settingsManager;

  /**
   * Constructs a WebformEntityThirdPartySettingsForm.
   *
   * @param \Drupal\webform\WebformThirdPartySettingsManagerInterface $settings_manager
   *   The third party settings manager.
   */
  public function __construct(WebformThirdPartySettingsManagerInterface $settings_manager) {
    $this->settingsManager = $settings_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('webform.third_party_settings_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = $this->settingsManager->buildForm($form, $form_state);
    $form_state->set('webform', $this->getEntity());
    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actionsElement(array $form, FormStateInterface $form_state) {
    $element = parent::actionsElement($form, $form_state);
    // Don't display the delete button.
    unset($element['delete']);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();
    $third_party_settings = $form_state->getValue('third_party_settings');
    foreach ($third_party_settings as $module => $third_party_setting) {
      foreach ($third_party_setting as $key => $value) {
        $webform->setThirdPartySetting($module, $key, $value);
      }
    }
    $webform->save();

    $context = [
      '@label' => $webform->label(),
      'link' => $webform->toLink($this->t('Edit'), 'third-party-settings-form')->toString()
    ];
    $this->logger('webform')->notice('Webform settings @label saved.', $context);

    drupal_set_message($this->t('Webform settings %label saved.', ['%label' => $webform->label()]));
  }

}
