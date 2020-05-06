<?php

namespace Drupal\webform\Form\AdminConfig;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webform\Element\WebformMessage;
use Drupal\webform\Plugin\WebformVariantManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure webform admin settings for variants.
 */
class WebformAdminConfigVariantsForm extends WebformAdminConfigBaseForm {

  /**
   * The webform variant manager.
   *
   * @var \Drupal\webform\Plugin\WebformVariantManagerInterface
   */
  protected $variantManager;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_admin_config_variants_form';
  }

  /**
   * Constructs a WebformAdminConfigVariantsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\webform\Plugin\WebformVariantManagerInterface $variant_manager
   *   The webform variant manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, WebformVariantManagerInterface $variant_manager) {
    parent::__construct($config_factory);
    $this->variantManager = $variant_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.webform.variant')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('webform.settings');

    // Display warning about needing 'Edit webform variants' permission.
    $t_args = [
      '@href' => Url::fromRoute('user.admin_permissions', [], ['fragment' => 'module-webform'])->toString(),
    ];
    if (!$this->currentUser()->hasPermission('edit webform variants')) {
      $form['message'] = [
        '#type' => 'webform_message',
        '#message_message' => $this->t('You need to be assigned <a href="@href">Edit webform variants</a> permission to be able create and manage variants.', $t_args),
        '#message_type' => 'warning',
      ];
    }
    else {
      $form['message'] = [
        '#type' => 'webform_message',
        '#message_message' => $this->t('Users need to be assigned <a href="@href">Edit webform variants</a> permission to be able create and manage variants.', $t_args),
        '#message_type' => 'info',
        '#message_close' => TRUE,
        '#message_storage' => WebformMessage::STORAGE_SESSION,
      ];
    }

    // Variant: Types.
    $form['variant_types'] = [
      '#type' => 'details',
      '#title' => $this->t('Variants'),
      '#description' => $this->t('Select available variants'),
      '#open' => TRUE,
      '#weight' => 10,
    ];
    $form['variant_types']['excluded_variants'] = $this->buildExcludedPlugins(
      $this->variantManager,
      $config->get('variant.excluded_variants') ?: []
    );
    $excluded_variant_checkboxes = [];
    foreach ($form['variant_types']['excluded_variants']['#options'] as $variant_id => $option) {
      if ($excluded_variant_checkboxes) {
        $excluded_variant_checkboxes[] = 'or';
      }
      $excluded_variant_checkboxes[] = [':input[name="excluded_variants[' . $variant_id . ']"]' => ['checked' => FALSE]];
    }
    $form['variant_types']['excluded_variants_message'] = [
      '#type' => 'webform_message',
      '#message_message' => $this->t('All excluded variants must be manually removed from existing webforms.'),
      '#message_type' => 'warning',
      '#states' => [
        'visible' => $excluded_variant_checkboxes,
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $excluded_variants = $this->convertIncludedToExcludedPluginIds($this->variantManager, $form_state->getValue('excluded_variants'));

    // Update config and submit form.
    $config = $this->config('webform.settings');
    $config->set('variant', ['excluded_variants' => $excluded_variants]);
    parent::submitForm($form, $form_state);
  }

}
