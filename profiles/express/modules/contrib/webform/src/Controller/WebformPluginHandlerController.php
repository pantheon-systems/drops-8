<?php

namespace Drupal\webform\Controller;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\webform\Utility\WebformDialogHelper;
use Drupal\webform\Plugin\WebformHandlerInterface;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for all webform handlers.
 */
class WebformPluginHandlerController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * A webform handler plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * Constructs a WebformPluginHanderController object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   *   A webform handler plugin manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, PluginManagerInterface $plugin_manager) {
    $this->configFactory = $config_factory;
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.webform.handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function index() {
    $excluded_handlers = $this->config('webform.settings')->get('handler.excluded_handlers');

    $definitions = $this->pluginManager->getDefinitions();
    $definitions = $this->pluginManager->getSortedDefinitions($definitions);

    $rows = [];
    foreach ($definitions as $plugin_id => $definition) {
      $rows[$plugin_id] = [
        'data' => [
          $plugin_id,
          $definition['label'],
          $definition['description'],
          $definition['category'],
          (isset($excluded_handlers[$plugin_id])) ? $this->t('Yes') : $this->t('No'),
          ($definition['cardinality'] == -1) ? $this->t('Unlimited') : $definition['cardinality'],
          $definition['conditions'] ? $this->t('Yes') : $this->t('No'),
          $definition['submission'] ? $this->t('Required') : $this->t('Optional'),
          $definition['results'] ? $this->t('Processed') : $this->t('Ignored'),
          $definition['provider'],
        ],
      ];
      if (isset($excluded_handlers[$plugin_id])) {
        $rows[$plugin_id]['class'] = ['color-warning'];
      }
    }
    ksort($rows);

    $build = [];

    // Settings
    $build['settings'] = [
      '#type' => 'link',
      '#title' => $this->t('Edit configuration'),
      '#url' => Url::fromRoute('webform.config.handlers'),
      '#attributes' => ['class' => ['button', 'button--small'], 'style' => 'float: right'],
    ];

    // Display info.
    $build['info'] = [
      '#markup' => $this->t('@total handlers', ['@total' => count($rows)]),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];

    // Handlers.
    $build['webform_handlers'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('ID'),
        $this->t('Label'),
        $this->t('Description'),
        $this->t('Category'),
        $this->t('Excluded'),
        $this->t('Cardinality'),
        $this->t('Conditional'),
        $this->t('Database'),
        $this->t('Results'),
        $this->t('Provided by'),
      ],
      '#rows' => $rows,
      '#sticky' => TRUE,
    ];

    return $build;
  }

  /**
   * Shows a list of webform handlers that can be added to a webform.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return array
   *   A render array as expected by the renderer.
   */
  public function listHandlers(Request $request, WebformInterface $webform) {
    $headers = [
      ['data' => $this->t('Handler'), 'width' => '20%'],
      ['data' => $this->t('Description'), 'width' => '40%'],
      ['data' => $this->t('Category'), 'width' => '20%'],
      ['data' => $this->t('Operations'), 'width' => '20%'],
    ];

    $definitions = $this->pluginManager->getDefinitions();
    $definitions = $this->pluginManager->getSortedDefinitions($definitions);
    $definitions = $this->pluginManager->removeExcludeDefinitions($definitions);

    $rows = [];
    foreach ($definitions as $plugin_id => $definition) {
      // Skip email handler which has dedicated button.
      if ($plugin_id == 'email') {
        continue;
      }

      // Check cardinality.
      $cardinality = $definition['cardinality'];
      $is_cardinality_unlimited = ($cardinality === WebformHandlerInterface::CARDINALITY_UNLIMITED);
      $is_cardinality_reached = ($webform->getHandlers($plugin_id)->count() >= $cardinality);
      if (!$is_cardinality_unlimited && $is_cardinality_reached) {
        continue;
      }

      $row = [];

      $row['title']['data'] = [
        '#type' => 'inline_template',
        '#template' => '<div class="webform-form-filter-text-source">{{ label }}</div>',
        '#context' => [
          'label' => $definition['label'],
        ],
      ];

      $row['description'] = [
        'data' => [
          '#markup' => $definition['description'],
        ],
      ];

      $row['category'] = $definition['category'];

      // Check submission required.
      $is_submission_required = ($definition['submission'] === WebformHandlerInterface::SUBMISSION_REQUIRED);
      $is_results_disabled = $webform->getSetting('results_disabled');
      if ($is_submission_required && $is_results_disabled) {
        $row['operations']['data'] = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => $this->t('Requires saving of submissions.'),
          '#attributes' => ['class' => ['color-warning']],
        ];
      }
      else {
        $links['add'] = [
          'title' => $this->t('Add handler'),
          'url' => Url::fromRoute('entity.webform.handler.add_form', ['webform' => $webform->id(), 'webform_handler' => $plugin_id]),
          'attributes' => WebformDialogHelper::getModalDialogAttributes(800),
        ];
        $row['operations']['data'] = [
          '#type' => 'operations',
          '#links' => $links,
          '#prefix' => '<div class="webform-dropbutton">',
          '#suffix' => '</div>',
        ];
      }

      $rows[] = $row;
    }

    $build['#attached']['library'][] = 'webform/webform.form';

    $build['filter'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter'),
      '#title_display' => 'invisible',
      '#size' => 30,
      '#placeholder' => $this->t('Filter by handler name'),
      '#attributes' => [
        'class' => ['webform-form-filter-text'],
        'data-element' => '.webform-handler-add-table',
        'title' => $this->t('Enter a part of the handler name to filter by.'),
        'autofocus' => 'autofocus',
      ],
    ];

    $build['handlers'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('No handler available.'),
      '#attributes' => [
        'class' => ['webform-handler-add-table'],
      ],
    ];

    return $build;
  }

}
