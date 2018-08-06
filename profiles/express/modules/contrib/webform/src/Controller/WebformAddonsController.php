<?php

namespace Drupal\webform\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\webform\Element\WebformMessage;
use Drupal\webform\WebformAddonsManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for webform add-on.
 */
class WebformAddonsController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The webform add-ons manager.
   *
   * @var \Drupal\webform\WebformAddonsManagerInterface
   */
  protected $addons;

  /**
   * Constructs a WebformAddonsController object.
   *
   * @param \Drupal\webform\WebformAddonsManagerInterface $addons
   *   The webform add-ons manager.
   */
  public function __construct(WebformAddonsManagerInterface $addons) {
    $this->addons = $addons;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('webform.addons_manager')
    );
  }

  /**
   * Returns the Webform extend page.
   *
   * @return array
   *   The webform submission webform.
   */
  public function index() {
    $build = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['webform-addons'],
      ],
    ];

    // Promotions.
    $build['promotions'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['webform-addons-promotions'],
      ],

    ];
    $promotions = $this->addons->getPromotions();
    foreach ($promotions as $promotion_name => $promotion) {
      $build['promotions'][$promotion_name] = [
        '#type' => 'webform_message',
        '#message_type' => $promotion_name,
        '#message_message' => $promotion['content'],
        '#message_close' => TRUE,
        '#message_id' => 'webform.addons.promotion.' . $promotion_name,
        '#message_storage' => WebformMessage::STORAGE_SESSION,
      ];
    }

    // Projects.
    $build['projects'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['webform-addons-projects', 'js-webform-details-toggle', 'webform-details-toggle'],
      ],
    ];
    $build['projects']['#attached']['library'][] = 'webform/webform.addons';

    $categories = $this->addons->getCategories();
    foreach ($categories as $category_name => $category) {
      $build['projects'][$category_name] = [
        '#type' => 'details',
        '#title' => $category['title'],
        '#attributes' => ['data-webform-element-id' => 'webform-addons-' . $category_name],
        '#open' => TRUE,
      ];
      $projects = $this->addons->getProjects($category_name);
      foreach ($projects as $project_name => &$project) {
        $project['description'] .= '<br /><small>' . $project['url']->toString() . '</small>';

        // Append recommended to project's description.
        if (!empty($project['recommended'])) {
          $project['description'] .= '<br /><b class="color-success"> ★' . $this->t('Recommended') . '</b>';
        }

        if (!empty($project['install']) && !$this->moduleHandler()->moduleExists($project_name)) {
          // If current user can install module then display a dismissible warning.
          if ($this->currentUser()->hasPermission('administer modules')) {
            $build['projects'][$project_name . '_message'] = [
              '#type' => 'webform_message',
              '#message_id' => $project_name . '_message',
              '#message_type' => 'warning',
              '#message_close' => TRUE,
              '#message_storage' => WebformMessage::STORAGE_USER,
              '#message_message' => $this->t('Please install to the <a href=":href">@title</a> project to improve the Webform module\'s user experience.', [':href' => $project['url']->toString(), '@title' => $project['title']]),
              '#weight' => -100,
            ];
          }
        }
      }

      $build['projects'][$category_name]['content'] = [
        '#theme' => 'admin_block_content',
        '#content' => $projects,
      ];
    }

    return $build;
  }

}
