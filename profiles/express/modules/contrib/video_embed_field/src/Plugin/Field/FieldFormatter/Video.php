<?php

namespace Drupal\video_embed_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\video_embed_field\ProviderManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the video field formatter.
 *
 * @FieldFormatter(
 *   id = "video_embed_field_video",
 *   label = @Translation("Video"),
 *   field_types = {
 *     "video_embed_field"
 *   }
 * )
 */
class Video extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The embed provider plugin manager.
   *
   * @var \Drupal\video_embed_field\ProviderManagerInterface
   */
  protected $providerManager;

  /**
   * The logged in user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new instance of the plugin.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\video_embed_field\ProviderManagerInterface $provider_manager
   *   The video embed provider manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The logged in user.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, $settings, $label, $view_mode, $third_party_settings, ProviderManagerInterface $provider_manager, AccountInterface $current_user) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->providerManager = $provider_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('video_embed_field.provider_manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    foreach ($items as $delta => $item) {
      $provider = $this->providerManager->loadProviderFromInput($item->value);

      if (!$provider) {
        $element[$delta] = ['#theme' => 'video_embed_field_missing_provider'];
      }
      else {
        $autoplay = $this->currentUser->hasPermission('never autoplay videos') ? FALSE : $this->getSetting('autoplay');
        $element[$delta] = $provider->renderEmbedCode($this->getSetting('width'), $this->getSetting('height'), $autoplay);
        $element[$delta]['#cache']['contexts'][] = 'user.permissions';

        // For responsive videos, wrap each field item in it's own container.
        if ($this->getSetting('responsive')) {
          $element[$delta] = [
            '#type' => 'container',
            '#attached' => ['library' => ['video_embed_field/responsive-video']],
            '#attributes' => ['class' => ['video-embed-field-responsive-video']],
            'children' => $element[$delta],
          ];
        }
      }

    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'responsive' => TRUE,
      'width' => '854',
      'height' => '480',
      'autoplay' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['autoplay'] = [
      '#title' => $this->t('Autoplay'),
      '#type' => 'checkbox',
      '#description' => $this->t('Autoplay the videos for users without the "never autoplay videos" permission. Roles with this permission will bypass this setting.'),
      '#default_value' => $this->getSetting('autoplay'),
    ];
    $elements['responsive'] = [
      '#title' => $this->t('Responsive Video'),
      '#type' => 'checkbox',
      '#description' => $this->t("Make the video fill the width of it's container, adjusting to the size of the user's screen."),
      '#default_value' => $this->getSetting('responsive'),
    ];
    // Loosely match the name attribute so forms which don't have a field
    // formatter structure (such as the WYSIWYG settings form) are also matched.
    $responsive_checked_state = [
      'visible' => [
        [
          ':input[name*="responsive"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $elements['width'] = [
      '#title' => $this->t('Width'),
      '#type' => 'number',
      '#field_suffix' => 'px',
      '#default_value' => $this->getSetting('width'),
      '#required' => TRUE,
      '#size' => 20,
      '#states' => $responsive_checked_state,
    ];
    $elements['height'] = [
      '#title' => $this->t('Height'),
      '#type' => 'number',
      '#field_suffix' => 'px',
      '#default_value' => $this->getSetting('height'),
      '#required' => TRUE,
      '#size' => 20,
      '#states' => $responsive_checked_state,
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $dimensions = $this->getSetting('responsive') ? $this->t('Responsive') : $this->t('@widthx@height', ['@width' => $this->getSetting('width'), '@height' => $this->getSetting('height')]);
    $summary[] = $this->t('Embedded Video (@dimensions@autoplay).', [
      '@dimensions' => $dimensions,
      '@autoplay' => $this->getSetting('autoplay') ? $this->t(', autoplaying') : '',
    ]);
    return $summary;
  }

  /**
   * Get an instance of the Video field formatter plugin.
   *
   * This is useful because there is a lot of overlap to the configuration and
   * display of a video in a WYSIWYG and configuring a field formatter. We
   * get an instance of the plugin with our own WYSIWYG settings shimmed in,
   * as well as a fake field_definition because one in this context doesn't
   * exist. This allows us to reuse aspects such as the form and settings
   * summary for the WYSIWYG integration.
   *
   * @param array $settings
   *   The settings to pass to the plugin.
   *
   * @return static
   *   The formatter plugin.
   */
  public static function mockInstance($settings) {
    return \Drupal::service('plugin.manager.field.formatter')->createInstance('video_embed_field_video', [
      'settings' => !empty($settings) ? $settings : [],
      'third_party_settings' => [],
      'field_definition' => new FieldConfig([
        'field_name' => 'mock',
        'entity_type' => 'mock',
        'bundle' => 'mock',
      ]),
      'label' => '',
      'view_mode' => '',
    ]);
  }

}
