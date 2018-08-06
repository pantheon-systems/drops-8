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
   * The add-ons manager.
   *
   * @var \Drupal\webform\WebformAddonsManagerInterface
   */
  protected $addons;

  /**
   * Constructs a WebformAddonsController object.
   *
   * @param \Drupal\webform\WebformAddonsManagerInterface $addons
   *   The add-ons manager.
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
        'class' => ['webform-addons', 'js-webform-details-toggle', 'webform-details-toggle'],
      ],
    ];
    $build['#attached']['library'][] = 'webform/webform.admin';
    $build['#attached']['library'][] = 'webform/webform.element.details.toggle';

    $categories = $this->addons->getCategories();
    foreach ($categories as $category_name => $category) {
      $build[$category_name] = [
        '#type' => 'details',
        '#title' => $category['title'],
        '#open' => TRUE,
      ];
      $projects = $this->addons->getProjects($category_name);
      foreach ($projects as $project_name => &$project) {
        $project['description'] .= '<br/><small>' . $project['url']->toString() . '</small>';

        if (!empty($project['recommended']) && !$this->moduleHandler()->moduleExists($project_name)) {

          // Append recommended to project's description.
          $project['description'] .= '<br/><b class="color-error">' . $this->t('Recommended') . '</b>';

          // If current user can install module then display a dismissible warning.
          if ($this->currentUser()->hasPermission('administer modules')) {
            $build[$project_name . '_message'] = [
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

      $build[$category_name]['content'] = [
        '#theme' => 'admin_block_content',
        '#content' => $projects,
      ];
    }
    return $build;
  }

}
