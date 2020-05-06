<?php

namespace Drupal\webform\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\Markup;
use Drupal\webform\Element\WebformMessage;
use Drupal\webform\WebformAddonsManagerInterface;
use Drupal\webform\WebformThemeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides route responses for Webform add-ons.
 */
class WebformAddonsController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The webform theme manager.
   *
   * @var \Drupal\webform\WebformThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The webform add-ons manager.
   *
   * @var \Drupal\webform\WebformAddonsManagerInterface
   */
  protected $addons;

  /**
   * Constructs a WebformAddonsController object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\webform\WebformThemeManagerInterface $theme_manager
   *   The webform theme manager.
   * @param \Drupal\webform\WebformAddonsManagerInterface $addons
   *   The webform add-ons manager.
   */
  public function __construct(RequestStack $request_stack, WebformThemeManagerInterface $theme_manager, WebformAddonsManagerInterface $addons) {
    $this->request = $request_stack->getCurrentRequest();
    $this->themeManager = $theme_manager;
    $this->addons = $addons;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('webform.theme_manager'),
      $container->get('webform.addons_manager')
    );
  }

  /**
   * Returns the Webform add-ons page.
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

    // Filter.
    $is_claro_theme = $this->themeManager->isActiveTheme('claro');
    $data_source = $is_claro_theme ? '.admin-item' : 'li';
    $data_parent = $is_claro_theme ? '.admin-item' : 'li';

    $build['filter'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter'),
      '#title_display' => 'invisible',
      '#size' => 30,
      '#placeholder' => $this->t('Filter by keyword'),
      '#attributes' => [
        'name' => 'text',
        'class' => ['webform-form-filter-text'],
        'data-summary' => '.webform-addons-summary',
        'data-item-singlular' => $this->t('add-on'),
        'data-item-plural' => $this->t('add-ons'),
        'data-no-results' => '.webform-addons-no-results',
        'data-element' => '.admin-list',
        'data-source' => $data_source,
        'data-parent' => $data_parent,
        'title' => $this->t('Enter a keyword to filter by.'),
        'autofocus' => 'autofocus',
      ],
    ];

    // Display info.
    $build['info'] = [
      '#markup' => $this->t('@total add-ons', ['@total' => count($this->addons->getProjects())]),
      '#prefix' => '<p class="webform-addons-summary">',
      '#suffix' => '</p>',
    ];

    // Projects.
    $build['projects'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['webform-addons-projects', 'js-webform-details-toggle', 'webform-details-toggle'],
      ],
    ];

    // Store and disable compact mode.
    // @see system_admin_compact_mode
    $system_admin_compact_mode = system_admin_compact_mode();
    $this->request->cookies->set('Drupal_visitor_admin_compact_mode', FALSE);

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
        // Append (Experimental) to title.
        if (!empty($project['experimental'])) {
          $project['title'] .= ' [' . $this->t('EXPERIMENTAL') . ']';
        }
        // Prepend logo to title.
        if (isset($project['logo'])) {
          $project['title'] = Markup::create('<img src="' . $project['logo']->toString() . '" alt="' . $project['title'] . '"/>' . $project['title']);
        }
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
              '#message_message' => $this->t('Please install to the <a href=":href">@title</a> project to improve the Webform module\'s user experience.', [':href' => $project['url']->toString(), '@title' => $project['title']]) .
                ' <em>' . $project['install'] . '</em>',
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

    // Reset compact mode to stored setting.
    $this->request->cookies->get('Drupal_visitor_admin_compact_mode', $system_admin_compact_mode);

    // No results.
    $build['no_results'] = [
      '#type' => 'webform_message',
      '#message_message' => $this->t('No add-ons found. Try a different search.'),
      '#message_type' => 'info',
      '#attributes' => ['class' => ['webform-addons-no-results']],
    ];

    $build['#attached']['library'][] = 'webform/webform.addons';
    $build['#attached']['library'][] = 'webform/webform.admin';

    return $build;
  }

}
