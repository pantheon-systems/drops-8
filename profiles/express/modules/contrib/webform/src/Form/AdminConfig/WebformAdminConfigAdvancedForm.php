<?php

namespace Drupal\webform\Form\AdminConfig;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure webform admin advanced settings.
 */
class WebformAdminConfigAdvancedForm extends WebformAdminConfigBaseForm {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The render cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $renderCache;

  /**
   * The router builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routerBuilder;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_admin_config_advanced_form';
  }

  /**
   * Constructs a WebformAdminConfigAdvancedForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $render_cache
   *   The render cache service.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $route_builder
   *   The router builder service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, CacheBackendInterface $render_cache, RouteBuilderInterface $router_builder) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
    $this->renderCache = $render_cache;
    $this->routerBuilder = $router_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('cache.render'),
      $container->get('router.builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('webform.settings');

    // UI.
    $form['ui'] = [
      '#type' => 'details',
      '#title' => $this->t('User interface settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['ui']['video_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Video display'),
      '#description' => $this->t('Controls how videos are displayed in inline help and within the global help section.'),
      '#options' => [
        'dialog' => $this->t('Dialog'),
        'link' => $this->t('External link'),
        'hidden' => $this->t('Hidden'),
      ],
      '#default_value' => $config->get('ui.video_display'),
    ];
    $form['ui']['description_help'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display element description as help text (tooltip)'),
      '#description' => $this->t("If checked, all element descriptions will be moved to help text (tooltip)."),
      '#return_value' => TRUE,
      '#default_value' => $config->get('ui.description_help'),
    ];
    $form['ui']['details_save'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Save details open/close state'),
      '#description' => $this->t('If checked, all <a href=":details_href">Details</a> element\'s open/close state will be saved using <a href=":local_storage_href">Local Storage</a>.', [
        ':details_href' => 'http://www.w3schools.com/tags/tag_details.asp',
        ':local_storage_href' => 'http://www.w3schools.com/html/html5_webstorage.asp',
      ]),
      '#return_value' => TRUE,
      '#default_value' => $config->get('ui.details_save'),
    ];
    $form['ui']['dialog_disabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable dialogs'),
      '#description' => $this->t('If checked, all modal dialogs (i.e. popups) will be disabled.'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('ui.dialog_disabled'),
    ];
    $form['ui']['about_disabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Disable the 'About' section"),
      '#description' => $this->t("If checked, 'About' section/tab will be remove from the admin UI."),
      '#return_value' => TRUE,
      '#default_value' => $config->get('ui.about_disabled'),
    ];
    $form['ui']['offcanvas_disabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable off-canvas system tray'),
      '#description' => $this->t('If checked, the off-canvas system tray will be disabled.'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('ui.offcanvas_disabled'),
      '#access' => $this->moduleHandler->moduleExists('outside_in') && (floatval(\Drupal::VERSION) >= 8.3),
      '#states' => [
        'visible' => [
          ':input[name="ui[dialog_disabled]"]' => [
            'checked' => FALSE,
          ],
        ],
      ],
    ];
    $form['ui']['promotions_disabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable promotions'),
      '#description' => $this->t('If checked, dismissible promotion messages that appear when the Webform module is updated will be disabled.') . ' ' .
        $this->t('Promotions on the <a href=":href">Webform: Add-ons</a> page will still be displayed.', [':href' => Url::fromRoute('webform.addons')->toString()]) . '<br/>' .
        $this->t('Note: Promotions are only visible to users who can <em>administer modules</em>.'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('ui.promotions_disabled'),
    ];
    if (!$this->moduleHandler->moduleExists('outside_in') && (floatval(\Drupal::VERSION) >= 8.3)) {
      $form['ui']['offcanvas_message'] = [
        '#type' => 'webform_message',
        '#message_type' => 'info',
        '#message_message' => $this->t('Enable the experimental <a href=":href">System tray module</a> to improve the Webform module\'s user experience.', [':href' => 'https://www.drupal.org/blog/drupal-82-now-with-more-outside-in']),
        '#states' => [
          'visible' => [
            ':input[name="ui[dialog_disabled]"]' => [
              'checked' => FALSE,
            ],
          ],
        ],
        '#weight' => -100,
      ];
    }

    // Requirements.
    $form['requirements'] = [
      '#type' => 'details',
      '#title' => $this->t('Requirements'),
      '#description' => $this->t('The below requirements are checked by the <a href=":href">Status report</a>.', [':href' => Url::fromRoute('system.status')->toString()]),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['requirements']['cdn'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Check if CDN is being used for external libraries'),
      '#description' => $this->t('If unchecked, all warnings about missing libraries will be disabled.'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('requirements.cdn'),
    ];
    $form['requirements']['bootstrap'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Check if the Webform Bootstrap Integration module is installed when using the Bootstrap theme'),
      '#description' => $this->t('If unchecked, all warnings about the Webform Bootstrap Integration module will be disabled.'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('requirements.bootstrap'),
    ];
    $form['requirements']['spam'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Check if SPAM protection module is installed'),
      '#description' => $this->t('If unchecked, all warnings about Webform SPAM protection will be disabled.'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('requirements.spam'),
    ];

    // Test.
    $form['test'] = [
      '#type' => 'details',
      '#title' => $this->t('Test settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['test']['types'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Test data by element type'),
      '#description' => $this->t("Above test data is keyed by element #type."),
      '#default_value' => $config->get('test.types'),
    ];
    $form['test']['names'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Test data by element name'),
      '#description' => $this->t("Above test data is keyed by full or partial element names. For example, using 'zip' will populate fields that are named 'zip' and 'zip_code' but not 'zipcode' or 'zipline'."),
      '#default_value' => $config->get('test.names'),
    ];

    // Batch.
    $form['batch'] = [
      '#type' => 'details',
      '#title' => $this->t('Batch settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['batch']['default_batch_export_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Batch export size'),
      '#min' => 1,
      '#required' => TRUE,
      '#default_value' => $config->get('batch.default_batch_export_size'),
    ];
    $form['batch']['default_batch_update_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Batch update size'),
      '#min' => 1,
      '#required' => TRUE,
      '#default_value' => $config->get('batch.default_batch_update_size'),
    ];
    $form['batch']['default_batch_delete_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Batch delete size'),
      '#min' => 1,
      '#required' => TRUE,
      '#default_value' => $config->get('batch.default_batch_delete_size'),
    ];
    $form['batch']['default_batch_email_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Batch email size'),
      '#description' => $this->t('Batch email size is used by any handler that sends out bulk emails. This include the scheduled email handler.'),
      '#min' => 1,
      '#required' => TRUE,
      '#default_value' => $config->get('batch.default_batch_email_size'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('webform.settings');
    $config->set('ui', $form_state->getValue('ui'));
    $config->set('requirements', $form_state->getValue('requirements'));
    $config->set('test', $form_state->getValue('test'));
    $config->set('batch', $form_state->getValue('batch'));
    $config->save();
    
    // Clear render cache so that local tasks can be updated.
    // @see webform_local_tasks_alter()
    $this->renderCache->deleteAll();
    \Drupal::service('router.builder')->rebuild();

    parent::submitForm($form, $form_state);
  }

}
