<?php

namespace Drupal\webform\Controller;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\webform\Utility\WebformDialogHelper;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for all webform variants.
 */
class WebformPluginVariantController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * A webform variant plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * Constructs a WebformPluginVariantController object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   *   A webform variant plugin manager.
   */
  public function __construct(PluginManagerInterface $plugin_manager) {
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.webform.variant')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function index() {
    $excluded_variants = $this->config('webform.settings')->get('variant.excluded_variants');

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
          (isset($excluded_variants[$plugin_id])) ? $this->t('Yes') : $this->t('No'),
          $definition['provider'],
        ],
      ];
      if (isset($excluded_variants[$plugin_id])) {
        $rows[$plugin_id]['class'] = ['color-warning'];
      }
    }
    ksort($rows);

    $build = [];

    // Settings.
    $build['settings'] = [
      '#type' => 'link',
      '#title' => $this->t('Edit configuration'),
      '#url' => Url::fromRoute('webform.config.variants'),
      '#attributes' => ['class' => ['button', 'button--small'], 'style' => 'float: right'],
    ];

    // Display info.
    $build['info'] = [
      '#markup' => $this->t('@total variants', ['@total' => count($rows)]),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];

    // Variants.
    $build['webform_variants'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('ID'),
        $this->t('Label'),
        $this->t('Description'),
        $this->t('Category'),
        $this->t('Excluded'),
        $this->t('Provided by'),
      ],
      '#rows' => $rows,
      '#sticky' => TRUE,
    ];

    return $build;
  }

  /**
   * Shows a list of webform variants that can be added to a webform.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return array
   *   A render array as expected by the renderer.
   */
  public function listVariants(Request $request, WebformInterface $webform) {
    // Get enabled variant types.
    $elements = $webform->getElementsVariant();
    $variant_types = [];
    foreach ($elements as $element_key) {
      $element = $webform->getElement($element_key);
      if (isset($element['#variant'])) {
        $variant_types[$element['#variant']] = $element['#variant'];
      }
    }

    $headers = [
      ['data' => $this->t('Variant'), 'width' => '20%'],
      ['data' => $this->t('Description'), 'width' => '40%'],
      ['data' => $this->t('Category'), 'width' => '20%'],
      ['data' => $this->t('Operations'), 'width' => '20%'],
    ];

    $definitions = $this->pluginManager->getDefinitions();
    $definitions = $this->pluginManager->getSortedDefinitions($definitions);
    $definitions = $this->pluginManager->removeExcludeDefinitions($definitions);

    $rows = [];
    foreach ($definitions as $plugin_id => $definition) {
      // Make sure variant type is enabled.
      if (!isset($variant_types[$plugin_id])) {
        continue;
      }

      $row = [];

      $row['title']['data'] = [
        '#type' => 'link',
        '#title' => $definition['label'],
        '#url' => Url::fromRoute('entity.webform.variant.add_form', ['webform' => $webform->id(), 'webform_variant' => $plugin_id]),
        '#attributes' => WebformDialogHelper::getOffCanvasDialogAttributes(),
        '#prefix' => '<div class="webform-form-filter-text-source">',
        '#suffix' => '</div>',
      ];

      $row['description'] = [
        'data' => [
          '#markup' => $definition['description'],
        ],
      ];

      $row['category'] = $definition['category'];

      $links['add'] = [
        'title' => $this->t('Add variant'),
        'url' => Url::fromRoute('entity.webform.variant.add_form', ['webform' => $webform->id(), 'webform_variant' => $plugin_id]),
        'attributes' => WebformDialogHelper::getOffCanvasDialogAttributes(),
      ];
      $row['operations']['data'] = [
        '#type' => 'operations',
        '#links' => $links,
        '#prefix' => '<div class="webform-dropbutton">',
        '#suffix' => '</div>',
      ];

      $rows[] = $row;
    }

    $build['filter'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter'),
      '#title_display' => 'invisible',
      '#size' => 30,
      '#placeholder' => $this->t('Filter by variant name'),
      '#attributes' => [
        'class' => ['webform-form-filter-text'],
        'data-element' => '.webform-variant-add-table',
        'data-item-singlular' => $this->t('variant'),
        'data-item-plural' => $this->t('variants'),
        'title' => $this->t('Enter a part of the variant name to filter by.'),
        'autofocus' => 'autofocus',
      ],
    ];

    $build['variants'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#sticky' => TRUE,
      '#empty' => $this->t('No variant available.'),
      '#attributes' => [
        'class' => ['webform-variant-add-table'],
      ],
    ];

    $build['#attached']['library'][] = 'webform/webform.form';
    $build['#attached']['library'][] = 'webform/webform.filter';

    return $build;
  }

}
