<?php

namespace Drupal\webform\Form\AdminConfig;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformLibrariesManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure webform admin settings for libraries.
 */
class WebformAdminConfigLibrariesForm extends WebformAdminConfigBaseForm {

  /**
   * The libraries manager.
   *
   * @var \Drupal\webform\WebformLibrariesManagerInterface
   */
  protected $librariesManager;

  /**
   * Array of webform library machine names.
   *
   * @var array
   */
  protected $libraries;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_admin_config_libraries_form';
  }

  /**
   * Constructs a WebformAdminConfigLibrariesForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\webform\WebformLibrariesManagerInterface $libraries_manager
   *   The webform libraries manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, WebformLibrariesManagerInterface $libraries_manager) {
    parent::__construct($config_factory);
    $this->librariesManager = $libraries_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('webform.libraries_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('webform.settings');

    // Assets.
    $form['assets'] = [
      '#type' => 'details',
      '#title' => $this->t('CSS / JavaScript'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['assets']['description'] = [
      '#type' => 'webform_message',
      '#message_message' => $this->t('The below CSS and JavasScript will be loaded on all webform pages.'),
      '#message_type' => 'info',
    ];
    $form['assets']['css'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'css',
      '#title' => $this->t('CSS'),
      '#description' => $this->t('Enter custom CSS to be attached to all webforms.'),
      '#default_value' => $config->get('assets.css'),
    ];
    $form['assets']['javascript'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'javascript',
      '#title' => $this->t('JavaScript'),
      '#description' => $this->t('Enter custom JavaScript to be attached to all webforms.'),
      '#default_value' => $config->get('assets.javascript'),
    ];

    // Libraries.
    $form['libraries'] = [
      '#type' => 'details',
      '#title' => $this->t('External libraries'),
      '#description' => $this->t('Uncheck the below optional external libraries that you do not want to be used by any webforms.') . '</br>' .
        '<em>' . $this->t('Please note, you can also exclude element types that are dependent on specific libraries.') . '</em>',
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $libraries_header = [
      'title' => ['data' => $this->t('Title')],
      'description' => ['data' => $this->t('Description/Notes'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
    ];

    $this->libraries = [];
    $libraries_options = [];
    $libraries = $this->librariesManager->getLibraries();
    foreach ($libraries as $library_name => $library) {
      // Only optional libraries can be excluded.
      if (empty($library['optional'])) {
        continue;
      }

      $this->libraries[$library_name] = $library_name;
      $libraries_options[$library_name] = [
        'title' => $library['title'],
        'description' => [
          'data' => [
            'content' => ['#markup' => $library['description'], '#suffix' => '<br />'],
            'notes' => ['#markup' => '(' . $library['notes'] . ')', '#prefix' => '<em>', '#suffix' => '</em><br />'],
          ],
        ],
      ];
    }
    $form['libraries']['excluded_libraries'] = [
      '#type' => 'tableselect',
      '#title' => $this->t('Libraries'),
      '#header' => $libraries_header,
      '#js_select' => FALSE,
      '#options' => $libraries_options,
      '#default_value' => array_diff($this->libraries, array_combine($config->get('libraries.excluded_libraries'), $config->get('libraries.excluded_libraries'))),
    ];
    $t_args = [
      ':select2_href' => $libraries['jquery.select2']['homepage_url']->toString(),
      ':chosen_href' => $libraries['jquery.chosen']['homepage_url']->toString(),
    ];
    $form['libraries']['select_message'] = [
      '#type' => 'webform_message',
      '#message_type' => 'warning',
      '#message_message' => $this->t('<a href=":select2_href">Select2</a> and <a href=":chosen_href">Chosen</a> provide very similar functionality, most websites should only have one of these libraries enabled.', $t_args),
      '#states' => [
        'visible' => [
          ':input[name="libraries[excluded_libraries][jquery.select2]"]' => ['checked' => TRUE],
          ':input[name="libraries[excluded_libraries][jquery.chosen]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Convert list of included types to excluded types.
    $libraries = $form_state->getValue('libraries');
    $libraries['excluded_libraries'] = array_diff($this->libraries, array_filter($libraries['excluded_libraries']));
    ksort($libraries['excluded_libraries']);

    // Note: Must store a simple array of libraries because library names
    // may contain periods, which is not supported by Drupal's
    // config management.
    $libraries['excluded_libraries'] = array_keys($libraries['excluded_libraries']);

    $config = $this->config('webform.settings');
    $config->set('assets', $form_state->getValue('assets'));
    $config->set('libraries', $libraries);
    $config->save();

    // Reset libraries cached.
    // @see webform_library_info_build()
    \Drupal::service('library.discovery')->clearCachedDefinitions();

    parent::submitForm($form, $form_state);
  }

}
