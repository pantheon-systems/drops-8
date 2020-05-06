<?php

namespace Drupal\webform\Form\AdminConfig;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webform\Element\WebformMessage;
use Drupal\webform\Plugin\WebformElement\TableSelect;
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
      '#description' => $this->t('Enter custom CSS to be attached to the all webforms.') . '<br/>' .
        $this->t("To customize only webform specific elements, you should use the '.webform-submission-form' selector"),
      '#default_value' => $config->get('assets.css'),
    ];
    $form['assets']['javascript'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'javascript',
      '#title' => $this->t('JavaScript'),
      '#description' => $this->t('Enter custom JavaScript to be attached to all webforms.'),
      '#default_value' => $config->get('assets.javascript'),
    ];

    // Libraries optional.
    $form['libraries_optional'] = [
      '#type' => 'details',
      '#title' => $this->t('External optional libraries'),
      '#description' => $this->t('Uncheck the below optional external libraries that you do not want to be used by any webforms.') . '</br>' .
        '<em>' . $this->t('Please note, you can also exclude element types that are dependent on specific libraries.') . '</em>',
      '#open' => TRUE,
    ];
    $libraries_header = [
      'title' => ['data' => $this->t('Title')],
      'version' => ['data' => $this->t('Version')],
      'description' => ['data' => $this->t('Description/Notes'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
      'elements' => ['data' => $this->t('Required elements'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
      'provider' => ['data' => $this->t('Provider'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
      'resources' => ['data' => $this->t('Resources'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
    ];

    $this->libraries = [];
    $libraries_optional_options = [];
    $libraries_required_option = [];
    $libraries = $this->librariesManager->getLibraries();
    foreach ($libraries as $library_name => $library) {
      $operations = [];
      $operations['homepage'] = [
        'title' => $this->t('Homepage'),
        'url' => $library['homepage_url'],
      ];
      if (isset($library['download_url'])) {
        $operations['download'] = [
          'title' => $this->t('Download'),
          'url' => $library['download_url'],
        ];
      }
      if (isset($library['issues_url'])) {
        $issues_url = $library['issues_url'];
      }
      elseif (isset($library['download_url']) && preg_match('#https://github.com/[^/]+/[^/]+#', $library['download_url']->toString(), $match)) {
        $issues_url = Url::fromUri($match[0] . '/issues');
      }
      else {
        $issues_url = NULL;
      }
      if ($issues_url) {
        $operations['issues'] = [
          'title' => $this->t('Open Issues'),
          'url' => $issues_url,
        ];
        $accessibility_url = clone $issues_url;
        $operations['accessibility'] = [
          'title' => $this->t('Accessibility Issues'),
          'url' => $accessibility_url->setOption('query', ['q' => 'is:issue is:open accessibility ']),
        ];
      }

      $library_option = [
        'title' => $library['title'],
        'version' => $library['version'],
        'description' => [
          'data' => [
            'content' => ['#markup' => $library['description'], '#suffix' => '<br />'],
            'notes' => ['#markup' => '(' . $library['notes'] . ')', '#prefix' => '<em>', '#suffix' => '</em><br />'],
            'status' => (!empty($library['deprecated'])) ? [
              '#markup' => $library['deprecated'],
              '#prefix' => '<div class="color-warning"><strong>',
              '#suffix' => '</strong></div>',
            ] : [],
          ],
        ],
        'elements' => ['data' => ['#markup' => (isset($library['elements'])) ? implode('<br/>', $library['elements']) : '']],
        'provider' => $library['provider'],
        'resources' => [
          'data' => [
            '#type' => 'operations',
            '#links' => $operations,
            '#prefix' => '<div class="webform-dropbutton">',
            '#suffix' => '</div>',
          ],
        ],
      ];

      // Only optional libraries can be excluded.
      if (empty($library['optional'])) {
        $libraries_required_options[$library_name] = $library_option;
      }
      else {
        $this->libraries[$library_name] = $library_name;
        $libraries_optional_options[$library_name] = $library_option;
      }
    }

    $form['libraries_optional']['excluded_libraries'] = [
      '#type' => 'tableselect',
      '#title' => $this->t('Libraries'),
      '#header' => $libraries_header,
      '#js_select' => FALSE,
      '#options' => $libraries_optional_options,
      '#default_value' => array_diff($this->libraries, array_combine($config->get('libraries.excluded_libraries'), $config->get('libraries.excluded_libraries'))),
    ];
    TableSelect::setProcessTableSelectCallback($form['libraries_optional']['excluded_libraries']);

    // Display warning message about select2, choices and chosen.
    $t_args = [
      ':select2_href' => $libraries['jquery.select2']['homepage_url']->toString(),
      ':choices_href' => $libraries['choices']['homepage_url']->toString(),
      ':chosen_href' => $libraries['jquery.chosen']['homepage_url']->toString(),
    ];
    $form['libraries_optional']['select_message'] = [
      '#type' => 'webform_message',
      '#message_type' => 'warning',
      '#message_message' => $this->t('<a href=":select2_href">Select2</a>, <a href=":choices_href">Choices</a>, and <a href=":chosen_href">Chosen</a> provide very similar functionality, most websites should only have one of these libraries enabled.', $t_args),
      '#message_close' => TRUE,
      '#message_storage' => WebformMessage::STORAGE_SESSION,
    ];

    // Libraries required.
    if ($libraries_required_option) {
      $form['libraries_required'] = [
        '#type' => 'details',
        '#title' => $this->t('External required libraries'),
        '#description' => $this->t('The below external libraries are required by specified webform elements or modules.'),
        '#open' => TRUE,
      ];
      $form['libraries_required']['required_libraries'] = [
        '#type' => 'table',
        '#header' => $libraries_header,
        '#rows' => $libraries_required_options,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Convert list of included types to excluded types.
    $excluded_libraries = array_diff($this->libraries, array_filter($form_state->getValue('excluded_libraries')));
    ksort($excluded_libraries);

    // Note: Must store a simple array of libraries because library names
    // may contain periods, which is not supported by Drupal's
    // config management.
    $excluded_libraries = array_keys($excluded_libraries);

    // Update config and submit form.
    $config = $this->config('webform.settings');
    $config->set('assets', $form_state->getValue('assets'));
    $config->set('libraries.excluded_libraries', $excluded_libraries);
    parent::submitForm($form, $form_state);

    // Reset libraries cached.
    // @see webform_library_info_build()
    \Drupal::service('library.discovery')->clearCachedDefinitions();
  }

}
