<?php

namespace Drupal\metatag_views\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Url;
use Drupal\metatag\MetatagManagerInterface;
use Drupal\metatag_views\Plugin\views\display_extender\MetatagDisplayExtender;
use Drupal\views\Plugin\views\display\DisplayPluginInterface;
use Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase;
use Drupal\views\ViewEntityInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MetatagViewsController
 * @package Drupal\metatag_views\Controller
 */
class MetatagViewsController extends ControllerBase {

  /** @var EntityStorageInterface  */
  protected $viewStorage;

  /** @var MetatagManagerInterface  */
  protected $metatagManager;

  /** @var array  associative array of labels */
  protected $viewLabels;

  public function __construct(EntityStorageInterface $viewStorage, MetatagManagerInterface $metatagManager) {
    $this->viewStorage = $viewStorage;
    $this->metatagManager = $metatagManager;

    // Generate the labels for views and displays.
    $this->labels = $this->getViewsAndDisplaysLabels();
  }

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('view'),
      $container->get('metatag.manager')
    );
  }

  /**
   * Get metatags for all of the views / displays that have them set.
   *
   * @return array
   *   List of tags grouped by view and display.
   */
  public static function getTaggedViews() {
    $tagged_views = [];
    foreach (Views::getEnabledViews() as $view_id => $view) {
      $displays = $view->get('display');
      foreach (array_keys($displays) as $display_id) {
        if ($tags = metatag_get_view_tags($view_id, $display_id)) {
          $tagged_views[$view_id][$display_id] = $tags;
        }
      }
    }
    return $tagged_views;
  }

  /**
   * Main controller function. Generates the renderable array for views
   * metatags UI.
   *
   * @return array
   */
  public function listViews() {
    $elements = [];

    $elements['header'] = [
      '#markup' => '<p>' . t("To view a list of displays with meta tags set up, click on a view name. To view a summary of meta tags configuration for a particular display, click on the display name. If you need to set metatags for a specific view, choose Add views meta tags. Reverting the meta tags removes the specific configuration and falls back to defaults.") . '</p>',
    ];

    // Iterate over the values and build the whole UI.
    // 1. Top level is a collapsible fieldset with a view name (details)
    // 2. Inside each fieldset we have 2 columns -> Display and Operations.
    //    Display contains point 3.
    //    Operations contain edit and revert
    // 3. In each display there is a table that has 2 columns: tag name and tag
    //    value.
    $tagged_views = $this->getTaggedViews();
    foreach ($tagged_views as $view_id => $displays) {
      $elements[$view_id] = [
        '#type' => 'details',
        '#title' => $this->t($this->viewLabels[$view_id]['#label']),
        'details' => $this->buildViewDetails($view_id, $displays),
      ];
    }

    return $elements;
  }

  /**
   * Builds the second "level" of the UI - table with display fieldset and operations.
   *
   * @param $view_id
   * @param $displays
   * @return array
   */
  protected function buildViewDetails($view_id, $displays) {
    $element = [
      '#type' => 'table',
      '#collapsible' => TRUE,
      '#header' => [$this->t('Display'), $this->t('Operations')],
    ];

    foreach ($displays as $display_id => $metatags) {
      $metatags = array_filter($metatags);

      $element[$display_id]['details'] = [
        '#type' => 'details',
        '#title' => $this->viewLabels[$view_id][$display_id],
      ];

      $params = ['view_id' => $view_id, 'display_id' => $display_id];

      // Generate the operations.
      $element[$display_id]['ops'] = [
        '#type' => 'operations',
        '#links' => [
          'edit' => [
            'title' => t('Edit'),
            'url' => Url::fromRoute('metatag_views.metatags.edit', $params),
          ],
          'translate' => [
            'title' => t('Translate'),
            'url' => Url::fromRoute('metatag_views.metatags.translate_overview', $params),
          ],
          'revert' => [
            'title' => t('Revert'),
            'url' => Url::fromRoute('metatag_views.metatags.revert', $params),
          ],
        ],
      ];

      // Build the rows for each of the metatag types.
      $element[$display_id]['details']['table'] = $this->buildDisplayDetailsTable($metatags);
    }

    return $element;
  }

  /**
   * Build the table with metatags values summary.
   *
   * @param $tags
   * @return array
   */
  protected function buildDisplayDetailsTable($tags) {
    $element = [
      '#type' => 'table',
    ];

    $i = 0;
    foreach ($tags as $tag_name => $tag_value) {
      // This is for the case where we have a subarray.
      $tag_value = $this->prepareTagValue($tag_value);
      if (!$tag_value) {
        continue;
      }

      $element[$i]['tag_name'] = [
        '#type' => 'markup',
        '#markup' => $tag_name,
      ];

      $element[$i]['tag_value'] = [
        '#type' => 'markup',
        '#markup' => $tag_value,
      ];
      $i++;
    }

    return $element;
  }

  /**
   * Massage the tag value. Returns an imploded string for metatags that
   * are nested (ex. robots).
   *
   * @param $value
   * @return string
   */
  protected function prepareTagValue($value) {
    if (is_array($value)) {
      $value = implode(', ', array_filter($value));
    }

    return $value;
  }

  /**
   * Gets label values for the views and their displays.
   */
  protected function getViewsAndDisplaysLabels() {
    /** @var ViewEntityInterface[] $views */
    $views = $this->viewStorage->loadByProperties(['status' => 1]);

    $labels = [];

    foreach ($views as $view_id => $view) {
      $displays = $view->get('display');
      $labels[$view_id]['#label'] = $view->label();
      foreach (array_keys($displays) as $display_id) {
        $labels[$view_id][$display_id] = $displays[$display_id]['display_title'];
      }
    }

    $this->viewLabels = $labels;
  }
}
