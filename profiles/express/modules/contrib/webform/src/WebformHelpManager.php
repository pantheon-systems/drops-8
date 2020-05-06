<?php

namespace Drupal\webform;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Url;
use Drupal\webform\Element\WebformMessage;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\webform\Utility\WebformDialogHelper;

/**
 * Webform help manager.
 */
class WebformHelpManager implements WebformHelpManagerInterface {

  use StringTranslationTrait;

  /**
   * Groups applied to help and videos.
   *
   * @var array
   */
  protected $groups;

  /**
   * Help for the Webform module.
   *
   * @var array
   */
  protected $help;

  /**
   * Videos for the Webform module.
   *
   * @var array
   */
  protected $videos;

  /**
   * The current version number of the Webform module.
   *
   * @var string
   */
  protected $version;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The Webform add-ons manager.
   *
   * @var \Drupal\webform\WebformAddonsManagerInterface
   */
  protected $addOnsManager;

  /**
   * The Webform libraries manager.
   *
   * @var \Drupal\webform\WebformLibrariesManagerInterface
   */
  protected $librariesManager;

  /**
   * Webform element manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * Constructs a WebformHelpManager object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher.
   * @param \Drupal\webform\WebformAddOnsManagerInterface $addons_manager
   *   The webform add-ons manager.
   * @param \Drupal\webform\WebformLibrariesManagerInterface $libraries_manager
   *   The webform libraries manager.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   */
  public function __construct(AccountInterface $current_user, ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, StateInterface $state, PathMatcherInterface $path_matcher, WebformAddOnsManagerInterface $addons_manager, WebformLibrariesManagerInterface $libraries_manager, WebformElementManagerInterface $element_manager) {
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->state = $state;
    $this->pathMatcher = $path_matcher;
    $this->addOnsManager = $addons_manager;
    $this->librariesManager = $libraries_manager;
    $this->elementManager = $element_manager;

    $this->groups = $this->initGroups();
    $this->help = $this->initHelp();
    $this->videos = $this->initVideos();
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup($id = NULL) {
    if ($id !== NULL) {
      return (isset($this->groups[$id])) ? $this->groups[$id] : NULL;
    }
    else {
      return $this->groups;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getHelp($id = NULL) {
    if ($id !== NULL) {
      return (isset($this->help[$id])) ? $this->help[$id] : NULL;
    }
    else {
      return $this->help;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getVideo($id = NULL) {
    if ($id !== NULL) {
      return (isset($this->videos[$id])) ? $this->videos[$id] : NULL;
    }
    else {
      return $this->videos;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getVideoLinks($id) {
    $video = $this->getVideo($id);

    // Presentation.
    $links = [];
    if (!empty($video['presentation_id'])) {
      $links[] = [
        'title' => $video['title'] . ' | ' . $this->t('Slides'),
        'url' => Url::fromUri('https://docs.google.com/presentation/d/' . $video['presentation_id']),
      ];
    }

    // Related resources.
    if (!empty($video['links'])) {
      foreach ($video['links'] as $link) {
        $link['url'] = Url::fromUri($link['url']);
        $links[] = $link;
      }
    }
    return $links;
  }

  /**
   * {@inheritdoc}
   */
  public function addNotification($id, $message, $type = 'status') {
    $notifications = $this->state->get('webform_help_notifications', []);
    $notifications[$type][$id] = $message;
    $this->state->set('webform_help_notifications', $notifications);
  }

  /**
   * {@inheritdoc}
   */
  public function getNotifications($type = NULL) {
    $notifications = $this->state->get('webform_help_notifications', []);
    if ($type) {
      return (isset($notifications[$type])) ? $notifications[$type] : [];
    }
    else {
      return $notifications;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteNotification($id) {
    $notifications = $this->state->get('webform_help_notifications', []);
    foreach ($notifications as &$messages) {
      unset($messages[$id]);
    }
    array_filter($notifications);
    $this->state->set('webform_help_notifications', $notifications);
  }

  /**
   * {@inheritdoc}
   */
  public function buildHelp($route_name, RouteMatchInterface $route_match) {
    $is_help_disabled = $this->configFactory->get('webform.settings')->get('ui.help_disabled');

    // Get path from route match.
    $path = preg_replace('/^' . preg_quote(base_path(), '/') . '/', '/', Url::fromRouteMatch($route_match)->setAbsolute(FALSE)->toString());

    $build = [];
    foreach ($this->help as $id => $help) {
      // Set default values.
      $help += [
        'routes' => [],
        'paths' => [],
        'access' => TRUE,
        'message_type' => '',
        'message_close' => FALSE,
        'message_id' => '',
        'message_storage' => '',
        'video_id' => '',
        'attached' => [],
      ];

      if (!$help['access']) {
        continue;
      }

      $is_route_match = in_array($route_name, $help['routes']);
      $is_path_match = ($help['paths'] && $this->pathMatcher->matchPath($path, implode(PHP_EOL, $help['paths'])));
      $has_help = ($is_route_match || $is_path_match);
      if (!$has_help) {
        continue;
      }

      // Check is help is disabled.  Messages are always displayed.
      if ($is_help_disabled && empty($help['message_type'])) {
        continue;
      }

      if ($help['message_type']) {
        $build[$id] = [
          '#type' => 'webform_message',
          '#message_type' => $help['message_type'],
          '#message_close' => $help['message_close'],
          '#message_id' => ($help['message_id']) ? $help['message_id'] : 'webform.help.' . $help['id'],
          '#message_storage' => $help['message_storage'],
          '#message_message' => [
            '#theme' => 'webform_help',
            '#info' => $help,
          ],
          '#attached' => $help['attached'],
        ];
        if ($help['message_close']) {
          $build['#cache']['max-age'] = 0;
        }
      }
      else {
        $build[$id] = [
          '#theme' => 'webform_help',
          '#info' => $help,
        ];
      }
      // Add custom help weight.
      if (isset($help['weight'])) {
        $build[$id]['#weight'] = $help['weight'];
      }
    }

    // Disable caching when Webform editorial module is enabled.
    if ($this->moduleHandler->moduleExists('webform_editorial') && $build) {
      $build['#cache']['max-age'] = 0;
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildIndex() {
    return $this->buildVideos();
  }

  /***************************************************************************/
  // Index sections.
  /***************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function buildVideos($docs = FALSE) {
    $video_display = $this->configFactory->get('webform.settings')->get('ui.video_display');
    $video_display = ($docs) ? 'documentation' : $video_display;
    if ($video_display === 'none') {
      return [];
    }

    $rows = [];
    foreach ($this->videos as $id => $video) {
      if (!empty($video['hidden'])) {
        continue;
      }

      $row = [];
      // Thumbnail.
      $video_thumbnail = [
        '#theme' => 'image',
        '#uri' => 'https://img.youtube.com/vi/' . $video['youtube_id'] . '/0.jpg',
        '#alt' => $video['title'],
      ];
      $row['thumbnail'] = [
        'data' => ['video' => $this->buildVideoLink($id, $video_display, $video_thumbnail, ['class' => [], 'more' => FALSE])],
        'width' => '200',
      ];
      // Content.
      $row['content'] = ['data' => []];
      $row['content']['data']['title'] = [
        '#markup' => $video['title'] . ' | ' . (isset($video['owner']) ? $video['owner'] : $this->t('Jacob Rockowitz')),
        '#prefix' => '<h3>',
        '#suffix' => '</h3>',
      ];
      $row['content']['data']['content'] = [
        '#markup' => $video['content'],
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ];
      $row['content']['data']['link'] = [
        'video' => $this->buildVideoLink($id, $video_display, NULL, ['more' => FALSE]),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ];
      if ($video_links = $this->getVideoLinks($id)) {
        $row['content']['data']['resources'] = [
          'title' => [
            '#markup' => $this->t('Additional resources'),
            '#prefix' => '<div><strong>',
            '#suffix' => '</strong></div>',
          ],
          'links' => [
            '#theme' => 'links',
            '#links' => $video_links,
            '#attributes' => ['class' => ['webform-help-links']],
          ],
        ];
      }
      $rows[$id] = ['data' => $row, 'no_striping' => TRUE];
    }

    $build = [
      'content' => [
        '#markup' => '<p>' . $this->t('The below are video tutorials are produced by <a href="https://jrockowitz.com">Jacob Rockowitz</a> and <a href="https://www.webwash.net/">WebWash.net</a>.') . '</p>' .
          (!$docs ? '<hr/>' : ''),
      ],
    ];

    if (!$docs) {
      // Filter.
      $build['filter'] = [
        '#type' => 'search',
        '#title' => $this->t('Filter'),
        '#title_display' => 'invisible',
        '#size' => 30,
        '#placeholder' => $this->t('Filter by videos'),
        '#attributes' => [
          'class' => ['webform-form-filter-text'],
          'data-element' => 'table',
          'data-source' => 'tbody tr',
          'data-parent' => 'tr',
          'data-summary' => '.webform-help-videos-summary',
          'data-item-singlular' => $this->t('video'),
          'data-item-plural' => $this->t('videos'),
          'data-no-results' => '.webform-help-videos-no-results',
          'title' => $this->t('Enter a keyword to filter by.'),
          'autofocus' => 'autofocus',
        ],
      ];

      // Display info.
      $build['info'] = [
        '#markup' => $this->t('@total videos', ['@total' => count($rows)]),
        '#prefix' => '<p class="webform-help-videos-summary">',
        '#suffix' => '</p>',
      ];

      // No results.
      $build['no_results'] = [
        '#type' => 'webform_message',
        '#message_message' => $this->t('No videos found. Try a different search.'),
        '#message_type' => 'info',
        '#attributes' => ['class' => ['webform-help-videos-no-results']],
      ];

      $build['table'] = [
        '#theme' => 'table',
        '#header' => [
          ['data' => '', 'style' => 'padding:0; border-top-color: transparent', 'class' => [RESPONSIVE_PRIORITY_LOW]],
          ['data' => '', 'style' => 'padding:0; border-top-color: transparent'],
        ],
        '#rows' => $rows,
        '#attributes' => [
          'border' => 0,
          'cellpadding' => 2,
          'cellspacing' => 0,
        ],
      ];

      $build['#attached']['library'][] = 'webform/webform.admin';
      $build['#attached']['library'][] = 'webform/webform.help';
      $build['#attached']['library'][] = 'webform/webform.ajax';
    }
    else {
      $build['videos'] = [
        '#theme' => 'table',
        '#rows' => $rows,
        '#no_striping' => TRUE,
        '#attributes' => [
          'border' => 0,
          'cellpadding' => 2,
          'cellspacing' => 0,
        ],
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildVideoLink($video_id, $video_display = NULL, $title = NULL, array $options = []) {
    $options += [
      'more' => TRUE,
      'class' => [
        'button',
        'button-action',
        'button--small',
        'button-webform-play',
      ],
    ];
    $video_info = $this->getVideo($video_id);
    if (empty($video_info['youtube_id'])) {
      return [];
    }

    $link = [
      '#type' => 'link',
      '#title' => $title ?: $this->t('Watch video'),
      '#prefix' => ' ',
    ];

    $video_display = $video_display ?: $this->configFactory->get('webform.settings')->get('ui.video_display');
    switch ($video_display) {
      case 'dialog':
        $route_name = 'webform.help.video';
        $route_parameters = ['id' => str_replace('_', '-', $video_info['id'])];
        $route_options = ($options['more']) ? ['query' => ['more' => 1]] : [];
        return [
          '#url' => Url::fromRoute($route_name, $route_parameters, $route_options),
          '#attributes' => WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_WIDE, $options['class']),
          '#attached' => ['library' => ['webform/webform.ajax']],
        ] + $link;

      case 'link':
        return [
          '#url' => Url::fromUri('https://youtu.be/' . $video_info['youtube_id']),
          '#attributes' => ['class' => $options['class']],
        ] + $link;

      case 'documentation':
        return [
          '#url' => Url::fromUri('https://youtu.be/' . $video_info['youtube_id']),
        ] + $link;

      case 'hidden':
      default:
        return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildAddOns($docs = FALSE) {
    $build = [
      'quote' => [
        '#markup' => '<table class="views-view-grid" width="100%"><tr>
<td><blockquote>' . $this->t('The Webform module for Drupal provides all the features expected from an enterprise proprietary form builder combined with the flexibility and openness of Drupal.') . '</blockquote></td>
<td width="100"><img src="https://www.drupal.org/files/webform_stacked-logo_256.png" width="256" alt="' . $this->t('Webform logo') . '" /></td>
</tr></table>',
        '#allowed_tags' => Xss::getAdminTagList(),
      ],
      'content' => [
        '#markup' => '<p>' . $this->t("Below is a list of modules and projects that extend and/or provide additional functionality to the Webform module and Drupal's Form API.") . '</p>' .
          '<hr/>' .
          '<p>★ = ' . $this->t('Recommended') . '</p>',
      ],
    ];

    $categories = $this->addOnsManager->getCategories();
    foreach ($categories as $category_name => $category) {
      $build['content'][$category_name]['title'] = [
        '#markup' => $category['title'],
        '#prefix' => '<h3 id="' . $category_name . '">',
        '#suffix' => '</h3>',
      ];
      $build['content'][$category_name]['projects'] = [
        '#prefix' => '<dl>',
        '#suffix' => '</dl>',
      ];
      $projects = $this->addOnsManager->getProjects($category_name);
      foreach ($projects as $project_name => $project) {
        $build['content'][$category_name]['projects'][$project_name] = [
          'title' => [
            '#type' => 'link',
            '#title' => Markup::create($project['title']
              . (!empty($project['experimental']) ? ' [' . $this->t('EXPERIMENTAL') . ']' : '')),
            '#url' => $project['url'],
            '#prefix' => '<dt>',
            '#suffix' => ((isset($project['recommended'])) ? ' ★' : '') . '</dt>',
          ],
          'description' => [
            '#markup' => $project['description'],
            '#prefix' => '<dd>',
            '#suffix' => '</dd>',
          ],
        ];
      }
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildLibraries($docs = FALSE) {
    $info = $this->getHelp('config_libraries_help');
    $build = [
      'content' => [
        'description' => [
          '#markup' => $info['content'],
          '#suffix' => '<p><hr /></p>',
        ],
        'libraries' => [
          '#prefix' => '<dl>',
          '#suffix' => '</dl>',
        ],
      ],
    ];
    $libraries = $this->librariesManager->getLibraries();
    foreach ($libraries as $library_name => $library) {
      if ($docs && !empty($library['deprecated'])) {
        continue;
      }

      // Get required elements.
      $elements = [];
      if (!empty($library['elements'])) {
        foreach ($library['elements'] as $element_name) {
          $element = $this->elementManager->getDefinition($element_name);
          $elements[] = $element['label'];
        }
      }

      $build['content']['libraries'][$library_name] = [
        'title' => [
          '#type' => 'link',
          '#title' => $library['title'],
          '#url' => $library['homepage_url'],
          '#prefix' => '<dt>',
          '#suffix' => ' (' . $library['version'] . ')</dt>',
        ],
        'description' => [
          'content' => [
            '#markup' => $library['description'],
            '#suffix' => '<br />',
          ],
          'notes' => [
            '#markup' => $library['notes'] .
              ($elements ? ' <strong>' . $this->formatPlural(count($elements), 'Required by @type element.', 'Required by @type elements.', ['@type' => WebformArrayHelper::toString($elements)]) . '</strong>' : ''),
            '#prefix' => '<em>(',
            '#suffix' => ')</em><br />',
          ],
          'download' => [
            '#type' => 'link',
            '#title' => $library['download_url']->toString(),
            '#url' => $library['download_url'],
          ],
          '#prefix' => '<dd>',
          '#suffix' => '</dd>',
        ],
      ];

      if ($docs) {
        $build['content']['libraries'][$library_name]['title']['#suffix'] = '</dt>';
        unset($build['content']['libraries'][$library_name]['description']['download']);

        if (isset($library['issues_url'])) {
          $issues_url = $library['issues_url'];
        }
        elseif (preg_match('#https://github.com/[^/]+/[^/]+#', $library['download_url']->toString(), $match)) {
          $issues_url = Url::fromUri($match[0] . '/issues');
        }
        else {
          $issues_url = NULL;
        }

        if ($issues_url) {
          $build['content']['libraries'][$library_name]['description']['accessibility'] = [
            '#type' => 'link',
            '#title' => $this->t('known accessibility issues'),
            '#url' => $issues_url->setOption('query', ['q' => 'is:issue is:open accessibility ']),
            '#prefix' => '<em>@see ',
            '#suffix' => '</em>',
          ];
        }
      }

    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildComparison($docs = FALSE) {
    // @see core/themes/seven/css/components/colors.css
    $group_color = '#dcdcdc';
    $feature_color = '#f5f5f5';
    $yes_color = '#d7ffd8';
    $no_color = '#ffffdd';
    $custom_color = '#ffece8';

    $content = file_get_contents('https://docs.google.com/spreadsheets/d/1zNt3WsKxDq2ZmMHeYAorNUUIx5_yiDtDVUIKXtXaq4s/pubhtml?gid=0&single=true');
    if (preg_match('#<table[^>]+>.*</table>#', $content, $match)) {
      $html = $match[0];
    }
    else {
      return [];
    }

    // Remove all attributes.
    $html = preg_replace('#(<[a-z]+) [^>]+>#', '\1>', $html);
    // Remove thead.
    $html = preg_replace('#<thead>.*</thead>#', '', $html);
    // Remove first th cell.
    $html = preg_replace('#<tr><th>.*?</th>#', '<tr>', $html);
    // Remove empty rows.
    $html = preg_replace('#<tr>(<td></td>)+?</tr>#', '', $html);
    // Remove empty links.
    $html = str_replace('<a>', '', $html);
    $html = str_replace('</a>', '', $html);

    // Add border and padding to table.
    if ($docs) {
      $html = str_replace('<table>', '<table border="1" cellpadding="2" cellspacing="1">', $html);
    }

    // Convert first row into <thead> with <th>.
    $html = preg_replace(
      '#<tbody><tr><td>(.+?)</td><td>(.+?)</td><td>(.+?)</td></tr>#',
      '<thead><tr><th width="30%">\1</th><th width="35%">\2</th><th width="35%">\3</th></thead><tbody>',
      $html
    );

    // Convert groups.
    $html = preg_replace('#<tr><td>([^<]+)</td>(<td></td>){2}</tr>#', '<tr><th bgcolor="' . $group_color . '">\1</th><th bgcolor="' . $group_color . '">Webform Module</th><th bgcolor="' . $group_color . '">Contact Module</th></tr>', $html);

    // Add cell colors.
    $html = preg_replace('#<tr><td>([^<]+)</td>#', '<tr><td bgcolor="' . $feature_color . '">\1</td>', $html);
    $html = preg_replace('#<td>Yes([^<]*)</td>#', '<td bgcolor="' . $yes_color . '"><img src="https://www.drupal.org/misc/watchdog-ok.png" alt="Yes"> \1</td>', $html);
    $html = preg_replace('#<td>No([^<]*)</td>#', '<td bgcolor="' . $custom_color . '"><img src="https://www.drupal.org/misc/watchdog-error.png" alt="No"> \1</td>', $html);
    $html = preg_replace('#<td>([^<]*)</td>#', '<td bgcolor="' . $no_color . '"><img src="https://www.drupal.org/misc/watchdog-warning.png" alt="Warning"> \1</td>', $html);

    // Link *.module.
    $html = preg_replace('/([a-z0-9_]+)\.module/', '<a href="https://www.drupal.org/project/\1">\1.module</a>', $html);

    // Convert URLs to links with titles.
    $links = [
      'https://www.drupal.org/docs/8/modules/webform' => $this->t('Webform Documentation'),
      'https://www.drupal.org/docs/8/core/modules/contact/overview' => $this->t('Contact Documentation'),
      'https://www.drupal.org/docs/8/modules/webform/webform-videos' => $this->t('Webform Videos'),
      'https://www.drupal.org/docs/8/modules/webform/webform-cookbook' => $this->t('Webform Cookbook'),
      'https://www.drupal.org/project/project_module?text=signature' => $this->t('Signature related-projects'),
      'https://www.drupal.org/sandbox/smaz/2833275' => $this->t('webform_slack.module'),
    ];
    foreach ($links as $link_url => $link_title) {
      $html = preg_replace('#([^"/])' . preg_quote($link_url, '#') . '([^"/])#', '\1<a href="' . $link_url . '">' . $link_title . '</a>\2', $html);
    }

    // Create fake filter object with settings.
    $filter = (object) ['settings' => ['filter_url_length' => 255]];
    $html = _filter_url($html, $filter);

    // Tidy.
    if (class_exists('\tidy')) {
      $tidy = new \tidy();
      $tidy->parseString($html, ['show-body-only' => TRUE, 'wrap' => '0'], 'utf8');
      $tidy->cleanRepair();
      $html = tidy_get_output($tidy);
    }

    return [
      'title' => [
        '#markup' => $this->t('Form builder comparison'),
        '#prefix' => '<h2 id="comparison">',
        '#suffix' => '</h2>',
      ],
      'content' => [
        '#prefix' => '<div>',
        '#suffix' => '</div>',
        'google' => [
          '#markup' => '<div class="note-warning"><p>' . $this->t('Please post comments and feedback to this <a href=":href">Google Sheet</a>.', [':href' => 'https://docs.google.com/spreadsheets/d/1zNt3WsKxDq2ZmMHeYAorNUUIx5_yiDtDVUIKXtXaq4s/edit?usp=sharing']) . '</p></div>',
        ],
        'description' => [
          '#markup' => '<p>' . $this->t("Here is a detailed feature-comparison of Webform 8.x-5.x and Contact Storage 8.x-1.x.&nbsp;It's worth noting that Contact Storage relies on the Contact module which in turn relies on the Field UI; Contact Storage out of the box is a minimalistic solution with limited (but useful!) functionality. This means it can be extended with core mechanisms such as CRUD entity hooks and overriding services; also there's a greater chance that a general purpose module will play nicely with it (eg. the Conditional Fields module is for entity form displays in general, not the Contact module).") . '</p>' .
            '<p>' . $this->t("Webform is much heavier; it has a great deal of functionality enabled right within the one module, and that's on top of supplying all the normal field elements (because it doesn't just use the Field API)") . '</p>',
        ],
        'table' => ['#markup' => $html],
      ],
    ];
  }

  /***************************************************************************/
  // Module.
  /***************************************************************************/

  /**
   * Get the current version number of the Webform module.
   *
   * @return string
   *   The current version number of the Webform module.
   */
  protected function getVersion() {
    if (isset($this->version)) {
      return $this->version;
    }

    $module_info = Yaml::decode(file_get_contents($this->moduleHandler->getModule('webform')->getPathname()));
    $this->version = (isset($module_info['version']) && !preg_match('/^8.x-5.\d+-.*-dev$/', $module_info['version'])) ? $module_info['version'] : '8.x-5.x-dev';
    return $this->version;
  }

  /**
   * Determine if the Webform module has been updated.
   *
   * @return bool
   *   TRUE if the Webform module has been updated.
   */
  protected function isUpdated() {
    return ($this->getVersion() !== $this->state->get('webform.version')) ? TRUE : FALSE;
  }

  /***************************************************************************/
  // Groups.
  /***************************************************************************/

  /**
   * Initialize group.
   *
   * @return array
   *   An associative array containing groups.
   */
  protected function initGroups() {
    return [
      'general' => $this->t('General'),
      'introduction' => $this->t('Introduction'),
      'about' => $this->t('About'),
      'installation' => $this->t('Installation'),
      'forms' => $this->t('Forms'),
      'elements' => $this->t('Elements'),
      'handlers' => $this->t('Handlers'),
      'settings' => $this->t('Settings'),
      'submissions' => $this->t('Submissions'),
      'submission' => $this->t('Submission'),
      'configuration' => $this->t('Configuration'),
      'plugins' => $this->t('Plugins'),
      'addons' => $this->t('Add-ons'),
      'webform_nodes' => $this->t('Webform Nodes'),
      'webform_blocks' => $this->t('Webform Blocks'),
      'translations' => $this->t('Translations'),
      'development' => $this->t('Development'),
      'messages' => $this->t('Messages'),
      'promotions' => $this->t('Promotions'),
    ];
  }

  /***************************************************************************/
  // Videos.
  /***************************************************************************/

  /**
   * Initialize videos.
   *
   * @return array
   *   An associative array containing videos.
   */
  protected function initVideos() {
    $videos = [];

    // Jacob Rockowitz (jrockowitz.com).
    $videos += [
      'introduction' => [
        'title' => $this->t('Introduction to Webform for Drupal 8'),
        'content' => $this->t('This screencast provides a general introduction to the Webform module.'),
        'youtube_id' => 'VncMRSwjVto',
        'presentation_id' => '1UmIdNe6ZOvddCVVzFgZ7RVAS5fa88gSumIfQLqd0gJo',
        'links' => [
          [
            'title' => $this->t('Getting Started with Webform in Drupal 8: Part I |  WebWash'),
            'url' => 'https://www.webwash.net/getting-started-webform-drupal-8/',
          ],
          [
            'title' => $this->t('Moving Forward with Webform in Drupal 8: Part II | WebWash'),
            'url' => 'https://www.webwash.net/moving-forward-webform-drupal-8/ ',
          ],
          [
            'title' => $this->t('How to Make an Advanced Webform in Drupal 8 | OSTrainging'),
            'url' => 'https://www.ostraining.com/blog/drupal/how-to-make-a-complex-webform-in-drupal-8/',
          ],
        ],
      ],
      'about' => [
        'title' => $this->t('About Webform & the Drupal community'),
        'content' => $this->t('This screencast introduces you to the maintainer and community behind the Webform module.'),
        'youtube_id' => 'DhNY4A-KRLY',
        'presentation_id' => '1uwQMoythumBWkWZgAsaWKoypl7KWWvztfCc6F6v2Vqk',
        'links' => [
          [
            'title' => $this->t('Where is the Drupal Community? | Drupal.org'),
            'url' => 'https://www.drupal.org/community',
          ],
          [
            'title' => $this->t('Getting Involved Guide | Drupal.org'),
            'url' => 'https://www.drupal.org/getting-involved-guide',
          ],
          [
            'title' => $this->t('Contributing to Drupal | Drupalize.me'),
            'url' => 'https://drupalize.me/topic/contributing-drupal',
          ],
          [
            'title' => $this->t('Connecting with the Community | Drupalize.me'),
            'url' => 'https://drupalize.me/tutorial/user-guide/thoughts-connecting',
          ],
          [
            'title' => $this->t('Concept: The Drupal Project | Drupalize.me'),
            'url' => 'https://drupalize.me/tutorial/user-guide/understanding-project',
          ],
          [
            'title' => $this->t('Concept: Drupal Licensing | Drupalize.me'),
            'url' => 'https://drupalize.me/tutorial/user-guide/understanding-gpl',
          ],
        ],
      ],
      'installation' => [
        'title' => $this->t('Installing the Webform module'),
        'content' => $this->t('This screencast walks through how to install the Webform and external libraries.'),
        'youtube_id' => '4QtVmKiak-c',
        'presentation_id' => '1S5wsXDOjU7mkvtTrUVqwZQeGSLi4c03GsoVcVrNTuUE',
        'links' => [
          [
            'title' => $this->t('Extending Drupal 8 | Drupal.org'),
            'url' => 'https://www.drupal.org/docs/8/extending-drupal-8',
          ],
          [
            'title' => $this->t('Installing a Module | Drupalize.me'),
            'url' => 'https://drupalize.me/tutorial/user-guide/config-install',
          ],
        ],
      ],
      'forms' => [
        'title' => $this->t('Building forms & templates'),
        'content' => $this->t('This screencast provides an overview of how to create, build, edit and test forms and templates.'),
        'youtube_id' => 'c7Vf0GUEhNs',
        'presentation_id' => '1Ka76boa2PYLBr6wUpIlNOJrzJZpK2QZTLdmfKDwLKic',
        'links' => [
          [
            'title' => $this->t('Form API | Drupal.org'),
            'url' => 'https://www.drupal.org/docs/8/api/form-api',
          ],
          [
            'title' => $this->t('Forms (Form API) | Drupalize.me'),
            'url' => 'https://drupalize.me/topic/forms-form-api',
          ],
          [
            'title' => $this->t('Form API Life Cycle | Drupalize.me'),
            'url' => 'https://drupalize.me/tutorial/form-api-life-cycle',
          ],
          [
            'title' => $this->t('Fun with Forms in Drupal 8 | DrupalCon Austin'),
            'url' => 'https://www.youtube.com/watch?v=WRW8qNiPTHk',
          ],
        ],
      ],
      'elements' => [
        'title' => $this->t('Adding elements to a webform'),
        'content' => $this->t('This screencast provides an overview of how to create, configure and manage form elements, layouts and multi-step wizards.'),
        'youtube_id' => 'u5EN3wjCZ2M',
        'presentation_id' => '1wy0uxKx9kHSTEGPBIPY6TXU1FVY05Z4iP35LXYYOeW8',
        'links' => [
          [
            'title' => $this->t('Render API | Drupal.org'),
            'url' => 'https://www.drupal.org/docs/8/api/render-api',
          ],
          [
            'title' => $this->t('Render arrays | Drupal.org'),
            'url' => 'https://www.drupal.org/docs/8/api/render-api/render-arrays',
          ],
          [
            'title' => $this->t('Render API Overview | Drupalize.me'),
            'url' => 'https://drupalize.me/tutorial/render-api-overview',
          ],
          [
            'title' => $this->t('Form Element Reference | Drupalize.me'),
            'url' => 'https://drupalize.me/tutorial/form-element-reference',
          ],
          [
            'title' => $this->t('What Are Render Elements? | Drupalize.me'),
            'url' => 'https://drupalize.me/tutorial/render-elements',
          ],
        ],
      ],
      'handlers' => [
        'title' => $this->t('Emailing & handling submissions'),
        'content' => $this->t('This screencast shows how to route submissions to external applications and send notifications & confirmations.'),
        'youtube_id' => 'oMCqqBJfWnk',
        'presentation_id' => '1SosCtHtEDHNriKF-y7Hji-5wPOa4XvWWvP13dFXG1AE',
        'links' => [
          [
            'title' => $this->t('Create a Webform Handler in Drupal 8 | Matt Arnold'),
            'url' => 'https://blog.mattarnster.co.uk/tutorials/create-a-webform-handler-in-drupal-8/',
          ],
          [
            'title' => $this->t('The Drupal mail system | Pronovix'),
            'url' => 'https://pronovix.com/blog/drupal-mail-system',
          ],
        ],
      ],
      'variants' => [
        'title' => $this->t('Webform variants'),
        'content' => $this->t("This screencast provides an overview of how to use webform variants to create A/B tests, audience segmentation, and personalization."),
        'youtube_id' => '53aB_mTkrI4',
        'presentation_id' => '1Pd_F8t82iXnNn87fWCj5r2zLh9je-SrCmrpOQjG-xCc',
        'links' => [

          [
            'title' => $this->t('Webform module now supports variants, which can be used for A/B tests, segmentation, and personalization'),
            'url' => 'https://www.drupal.org/node/3104280',
          ],
          [
            'title' => $this->t('Personalized Webforms'),
            'url' => 'https://www.jrockowitz.com/blog/personalized-webforms',
          ],
        ],
      ],
      'settings' => [
        'title' => $this->t('Configuring webform settings'),
        'content' => $this->t("This screencast shows how to configure a form's general settings, submission handling, confirmation message/page, custom CSS/JS and access controls."),
        'youtube_id' => 'Dm8EX-9VM3U',
        'presentation_id' => '1MYEKEbJYhyLRIPUCYMqixsR2X_Ss_zPT7oxvXMOfLbU',
      ],
      'submissions' => [
        'title' => $this->t('Collecting webform submissions'),
        'content' => $this->t("This screencast shows how to manage, review and export a form's submissions."),
        'youtube_id' => 'DUO54Suz-3A',
        'presentation_id' => '11N4UHJo7ohxGg1WqKQsXkHDNMehajKttdUf8o8PB22o',
      ],
      'submission' => [
        'title' => $this->t('Understanding a webform submission'),
        'content' => $this->t("This screencast shows how to review, edit, resend and administer a  submission."),
        'youtube_id' => '2odyu1Muwy0',
        'presentation_id' => '1ItsdeMHKzQICoMH4GPV7cEj5CidDjn-uQP9nWTDrWGM',
        'links' => [
          [
            'title' => $this->t('Entity–attribute–value model | Wikipedia'),
            'url' => 'https://en.wikipedia.org/wiki/Entity–attribute–value_model',
          ],
        ],
      ],
      'import' => [
        'title' => $this->t('Importing webform submissions'),
        'content' => $this->t("This screencast shows how to import submissions using CSV (comma separated values) file."),
        'youtube_id' => 'AYGr4O-jZBo',
        'presentation_id' => '189XhD6m0879EMo44ym8uaZaIAFiEl8tkH31WUtge_u8',
        'links' => [
          [
            'title' => $this->t('Webform module now supports importing submissions | Drupal.org'),
            'url' => 'https://www.drupal.org/node/3040513',
          ],
        ],
      ],
      'configuration' => [
        'title' => $this->t("Configuring the Webform module"),
        'content' => $this->t('This screencast walks through all the configuration settings available to manage forms, submissions, options, handlers, exporters, libraries and assets.'),
        'youtube_id' => '0buvEx8xHgg',
        'presentation_id' => '1Wr2W47eYDIEP6DOzhBXciLPZjltOIruUIC_FKgGDnwI',
        'links' => [
          [
            'title' => $this->t('How to Use Webform Predefined Options in Drupal 8 | WebWash'),
            'url' => 'https://www.webwash.net/use-webform-predefined-options-drupal-8/',
          ],
          [
            'title' => $this->t('Understanding Hooks | Drupal.org'),
            'url' => 'https://www.drupal.org/docs/8/creating-custom-modules/understanding-hooks',
          ],
          [
            'title' => $this->t('What Are Hooks? | Drupalize.me'),
            'url' => 'https://drupalize.me/tutorial/what-are-hooks',
          ],
        ],
      ],
      'access' => [
        'title' => $this->t("Webform access controls"),
        'content' => $this->t('This screencast walks through how to use permissions, roles, and custom access rules to control access to webforms and submissions.'),
        'youtube_id' => 'EPg9Ltwak2M',
        'presentation_id' => '19Xkb2MR5N075Va403slTVRYjanJ14HmuEYBwwbrQFX4',
        'links' => [
          [
            'title' => $this->t('Users, Roles, and Permissions | Drupal.org'),
            'url' => 'https://drupal.org/docs/user_guide/en/user-concept.html ',
          ],
          [
            'title' => $this->t('Users, Roles, and Permissions | Drupalize.me'),
            'url' => 'https://drupalize.me/topic/users-roles-and-permissions',
          ],
          [
            'title' => $this->t('Access Control | Tag1 Consulting'),
            'url' => 'https://tag1consulting.com/blog/access-control',
          ],
        ],
      ],
      'webform_nodes' => [
        'title' => $this->t('Attaching webforms to nodes'),
        'content' => $this->t('This screencast walks through how to attach a webform to node.'),
        'youtube_id' => 'B_ZyCOVKPqA',
        'presentation_id' => '1XoIUSgQ0bb_xCfWx8VZe1WHTr0QoCfnE8DzSAsc2WQM',
        'links' => [
          [
            'title' => $this->t('Working with content types and fields | Drupal.org'),
            'url' => 'https://www.drupal.org/docs/8/administering-drupal-8-site/managing-content-0/working-with-content-types-and-fields',
          ],
          [
            'title' => $this->t('What Are Drupal Entities? | Drupalize.me'),
            'url' => 'https://drupalize.me/videos/what-are-drupal-entities',
          ],
          [
            'title' => $this->t('Concept: Content Entities and Fields | Drupalize.me'),
            'url' => 'https://drupalize.me/tutorial/user-guide/planning-data-types',
          ],
        ],
      ],
      'webform_blocks' => [
        'title' => $this->t('Placing webforms as blocks'),
        'content' => $this->t('This screencast walks through how to place a webform on a website as a block.'),
        'youtube_id' => 'twsawm5pbjI',
        'presentation_id' => '12H1ecphNlulggehltnaS6FWN2hJlwbILULge1WRxYWY',
        'links' => [
          [
            'title' => $this->t('Working with blocks | Drupal.org'),
            'url' => 'https://www.drupal.org/docs/8/core/modules/block/overview',
          ],
          [
            'title' => $this->t('Blocks | Drupalize.me'),
            'url' => 'https://drupalize.me/topic/blocks',
          ],
        ],
      ],
      'addons' => [
        'title' => $this->t('Extending Webform using add-ons'),
        'content' => $this->t("This screencast suggests and recommends additional Drupal projects that can be installed to enhance, improve and alter the Webform module's functionality."),
        'youtube_id' => '2sthMx6adl4',
        'presentation_id' => '1azK1xkHH4-tiQ9TV8GDqVKk4FXgxarM6MPrBWCLljiQ',
        'links' => [
          [
            'title' => $this->t('Extend Drupal with Modules | Drupalize.me'),
            'url' => 'https://drupalize.me/topic/extend-drupal-modules',
          ],
          [
            'title' => $this->t('Download & Extend | Drupal.org'),
            'url' => 'https://www.drupal.org/project/project_module',
          ],
        ],
      ],
      'plugins' => [
        'title' => $this->t("Understanding webform plugins"),
        'content' => $this->t("This screencast offers an overview of the Webform module's element, handler and exporter plugins."),
        'youtube_id' => 'nCSr71mfBR4',
        'presentation_id' => '1SrcG1vJpWlarLW-cJQDsP4QsAzeyrox7HXBcYMFUsQE',
        'links' => [
          [
            'title' => $this->t('Why Plugins? | Drupal.org'),
            'url' => 'https://www.drupal.org/docs/8/api/plugin-api/why-plugins',
          ],
          [
            'title' => $this->t('Plugins | Drupalize.me'),
            'url' => 'https://drupalize.me/topic/plugins',
          ],
          [
            'title' => $this->t('Unraveling the Drupal 8 Plugin System | Drupalize.me'),
            'url' => 'https://drupalize.me/blog/201409/unravelling-drupal-8-plugin-system',
          ],
        ],
      ],
      'dialogs' => [
        'title' => $this->t('Opening webforms in modal dialogs'),
        'content' => $this->t('This screencast shows how to open webforms in modal dialogs.'),
        'youtube_id' => 'zmRxyUHWczw',
        'presentation_id' => '1XlAv-u1lZr13nZvCEuJXtDp4Dmn8X7Fwq_4yac-SajE',
        'links' => [
          [
            'title' => $this->t('Creating a modal in Drupal 8 | Befused'),
            'url' => 'https://www.drupal.org/project/devel',
          ],
          [
            'title' => $this->t('Display forms in a modal dialog with Drupal 8 | Agaric'),
            'url' => 'http://agaric.com/blogs/display-forms-modal-dialog-drupal-8',
          ],
          [
            'title' => $this->t('jQueryUI Dialog Documentation'),
            'url' => 'https://jqueryui.com/dialog/  ',
          ],
        ],
      ],
      'views' => [
        'title' => $this->t('Webform views integration'),
        'content' => $this->t('This presentation shows how to use views to display webform submissions.'),
        'youtube_id' => 'Qs_m5ybxeXk',
        'presentation_id' => '1pUUmwjsyxtU9YB4y0qQbSING1W4YBTcqYQjabmSL5N8',
        'links' => [
          [
            'title' => $this->t('Views module | Drupal.org'),
            'url' => 'https://www.drupal.org/docs/8/core/modules/views',
          ],
          [
            'title' => $this->t('Webform Views Integration | Drupal.org'),
            'url' => 'https://www.drupal.org/project/webform_views',
          ],
          [
            'title' => $this->t('D8 Webform and Webform Views Integration @ Drupalcamp Colorado'),
            'url' => 'https://www.youtube.com/watch?v=Riw9g_y1A_s',
          ],
        ],
      ],
      'attachments' => [
        'title' => $this->t('Sending webform email attachments'),
        'content' => $this->t('This presentation shows how to set up and add email attachments via an email handler.'),
        'youtube_id' => 'w7exQFDIHhQ',
        'presentation_id' => '1DTE9nSg_CKhWkhBCmfks_o2RoeApTHc4orhNxrj2imk',
        'links' => [
          [
            'title' => $this->t('How to send email attachments? | Drupal.org'),
            'url' => 'https://www.drupal.org/node/3021480 ',
          ],
          [
            'title' => $this->t('Webform Attachment sub-module | Drupal.org'),
            'url' => 'https://www.drupal.org/node/3021481',
          ],
        ],
      ],
      'limits' => [
        'title' => $this->t('Submission limits and options limits'),
        'content' => $this->t("This screencast shows how to set submission limits and options limits."),
        'youtube_id' => 'fdkv10v3AX4',
        'presentation_id' => '1owgZ4ueFagynwnzvBsH6krpvLqMXunMJXD32BqMCC-E',
        'links' => [
          [
            'title' => $this->t('Webform now supports option limits as well as submission limits'),
            'url' => 'https://www.drupal.org/node/3080869',
          ],
        ],
      ],
      'custom_options' => [
        'title' => $this->t('Webform custom options elements'),
        'content' => $this->t("The screencast walks through creating custom webform options elements."),
        'youtube_id' => '08Ze1eACM48',
        'presentation_id' => '1MZQ0we3qG9G3eFLtnHXiQ5c_uDfn1jjiBHciAeW311g',
        'links' => [
          [
            'title' => $this->t('Webform module supports creating custom elements using HTML and SVG markup'),
            'url' => 'https://www.drupal.org/node/3089024',
          ],
        ],
      ],
      'print' => [
        'title' => $this->t('Printing webform submissions as PDF documents'),
        'content' => $this->t("This screencast shows how to download, export, and email PDF copies of webform submissions."),
        'youtube_id' => 'Zj1HQNGTHFI',
        'presentation_id' => '1Sp3aam87-wkGpEfJqTxIgVXh0JIquwY-MgXe_7QviuQ',
        'links' => [
          [
            'title' => $this->t('Entity Print | Drupal.org'),
            'url' => 'https://www.drupal.org/project/entity_print',
          ],
          [
            'title' => $this->t('Webform module now supports printing PDF documents | jrockowitz.com'),
            'url' => 'https://www.jrockowitz.com/blog/webform-entity-print',
          ],
        ],
      ],
      'translations' => [
        'title' => $this->t('Translating webforms'),
        'content' => $this->t("This screencast shows how to translate a webform's title, descriptions, label and messages."),
        'youtube_id' => 'dfG37uW5Qu8',
        'presentation_id' => '1TjQJMtNTSyQ4i881B_kMalqqVR3QEFoNgNJIotGNXyY',
        'links' => [
          [
            'title' => $this->t('Translating configuration | Drupal.org'),
            'url' => 'https://www.drupal.org/docs/8/multilingual/translating-configuration',
          ],
          [
            'title' => $this->t('Translating Configuration | Drupalize.me'),
            'url' => 'https://drupalize.me/tutorial/user-guide/language-config-translate',
          ],
        ],
      ],
      'development' => [
        'title' => $this->t('Webform development tools'),
        'content' => $this->t('This screencast gives developers an overview of the tools available to help build, debug and export forms.'),
        'youtube_id' => '4xI-T1OuHn4',
        'presentation_id' => '1vMt2mXhkswjOqfh7AvBQm6jN9dFrfFv5Fd1It-EEHyo',
        'links' => [
          [
            'title' => $this->t('Devel | Drupal.org'),
            'url' => 'https://www.drupal.org/project/devel',
          ],
          [
            'title' => $this->t('Devel | Drupalize.me'),
            'url' => 'https://drupalize.me/topic/devel',
          ],
          [
            'title' => $this->t('Configuration API for Developers | Drupalize.me'),
            'url' => 'https://drupalize.me/topic/configuration-api-developers',
          ],
        ],
      ],
      'api_reuse' => [
        'title' => $this->t('Reusing Webform APIs'),
        'content' => $this->t('This screencast walks through how to reusing the Webform module’s APls to create custom configuration forms.'),
        'youtube_id' => 't8cIZuAjYck',
        'presentation_id' => '11IdSeA_UwT2nbE3jlDEYrESov_mEkL4ehu_Sf1j-eww',
        'links' => [
          [
            'title' => $this->t('Form API | Drupal.org'),
            'url' => 'https://www.drupal.org/project/devel',
          ],
          [
            'title' => $this->t('Examples for Developers | Drupal.org'),
            'url' => 'https://www.drupal.org/project/examples',
          ],
        ],
      ],
      'composites_vs_tables' => [
        'title' => $this->t('Webform Composites vs. Tables'),
        'content' => $this->t('This screencast walks through when to use a webform composite element and when to use a webform table.'),
        'youtube_id' => '7cVIqySy5fs',
        'presentation_id' => '1R13ZGkNgTkxjlN-BT05zrwW2JKhOcvGiByNYl7qtywg',
      ],

      'webform' => [
        'title' => $this->t('Webform: There is this for that'),
        'content' => $this->t('One of the key mantras in the Drupal is “there is a module for that, “ and Webform is the module for building forms for Drupal 8.'),
        'youtube_id' => 'zl_ErUKymYo',
        'presentation_id' => '14vpNvDhYKGhHspu9BurIneTL4C1spyfwsqI82MvTYUA',
      ],
      'accessibility' => [
        'title' => $this->t('Webform Accessibility'),
        'content' => $this->t('This presentation is about approaching accessibility using the Webform module for Drupal 8.'),
        'youtube_id' => 'JR0wnd6Orfk',
        'presentation_id' => '1ni2a9id7VT67uO3f0i1UMt9_dswfcSHW1gZcXGCSEcM',
        'links' => [
          [
            'title' => $this->t('Accessibility | Drupal.org'),
            'url' => 'https://www.drupal.org/about/features/accessibility',
          ],
          [
            'title' => $this->t('Drupal 8 Accessibility'),
            'url' => 'https://www.drupal.org/docs/8/accessibility',
          ],
          [
            'title' => $this->t('Webform Accessibility'),
            'url' => 'https://www.drupal.org/docs/8/modules/webform/webform-accessibility',
          ],
        ],
      ],
      'demo' => [
        'title' => $this->t('Webform Demo'),
        'content' => $this->t('This presentation demonstrates how to build a feedback form and an event registration system using the Webform module.'),
        'youtube_id' => 'NPhQoSyD8D8',
        'presentation_id' => '17U1PCV1BQusYq3RnaYMi_zi0iba422SkA6ndQZbr99k',
      ],
      'advanced' => [
        'title' => $this->t('Advanced Webforms'),
        'content' => $this->t('This presentation gives you the extra knowledge you need to get the most out the Webform module.'),
        'youtube_id' => 'Yg2lAzE1heM',
        'presentation_id' => '1TMo0vBjkdtfcIsYWhxQnjO_rG9ebK64oHhdPvTvwNus',
      ],
      'healthcare' => [
        'title' => $this->t('Webforms for Healthcare'),
        'content' => $this->t('This presentation discusses how healthcare organizations can leverage the Webform module for Drupal 8.'),
        'youtube_id' => 'YiK__YobDJw',
        'presentation_id' => '1jxbJkovaubHrhvjIZ-_OoK0zsANqC1vG4HFvAxfszOE/edit',
      ],
      'designers' => [
        'title' => $this->t('Webforms for Designers'),
        'content' => $this->t('This presentation introduces designers to the Webform module for Drupal 8.'),
        'youtube_id' => '-7lxtfYgidY',
        'presentation_id' => '1agZ7Mq0UZBn746dKRbWjQCYvd8HptlejtPhUIuQ2IrE',
      ],
      'government' => [
        'title' => $this->t('Webforms for Government'),
        'content' => $this->t('This screencast will explore how governments can leverage the Webform module for Drupal 8 to build accessible forms that securely collective massive amounts of data.'),
        'youtube_id' => 'WQG6163r9Rs',
        'presentation_id' => '1Mn7qlSR_njTZcGAM3PNQZR8Tvg7qtPhZFQja7Mj5uzI',
      ],
    ];

    // WebWash (www.webwash.net/).
    $videos += [
      'webwash_webform' => [
        'title' => $this->t('How to Create Forms using Webform and Contact in Drupal 8'),
        'owner' => $this->t('WebWash'),
        'content' => $this->t('Learn how to create forms using Webform and Contact module in Drupal 8.'),
        'youtube_id' => 'u8PBW0K9I9I',
        'links' => [
          [
            'title' => $this->t('Getting Started with Webform in Drupal 8: Part I |  WebWash'),
            'url' => 'https://www.webwash.net/getting-started-webform-drupal-8/',
          ],
          [
            'title' => $this->t('Moving Forward with Webform in Drupal 8: Part II | WebWash'),
            'url' => 'https://www.webwash.net/moving-forward-webform-drupal-8/ ',
          ],
        ]
      ],
      'webwash_install' => [
        'title' => $this->t('Using Webform in Drupal 8, 1.1: Install Webform'),
        'owner' => $this->t('WebWash'),
        'content' => $this->t('Learn how to download and install the Webform module.'),
        'youtube_id' => 'T4CiLF8fwFQ',
      ],
      'webwash_create' => [
        'title' => $this->t('Using Webform in Drupal 8, 1.2: Create a Form'),
        'owner' => $this->t('WebWash'),
        'content' => $this->t('Learn how to create a form from scratch and add three elements to it: Name, Email and Telephone.'),
        'youtube_id' => 'fr3kTiYKNls',
      ],
      'webwash_conditional' => [
        'title' => $this->t('Using Webform in Drupal 8, 2.1: Create Conditional Elements'),
        'owner' => $this->t('WebWash'),
        'content' => $this->t('Learn how to create conditional elements.'),
        'youtube_id' => 'ic4wu-iZd4Y',
      ],
      'webwash_wizard' => [
        'title' => $this->t('Using Webform in Drupal 8, 2.2: Create Multi-step Wizard'),
        'owner' => $this->t('WebWash'),
        'content' => $this->t('Learn how to create a multi-step page form.'),
        'youtube_id' => 'k17W2yH71ak',
      ],
      'webwash_float' => [
        'title' => $this->t('Using Webform in Drupal 8, 2.3: Float Elements Next to Each Other'),
        'owner' => $this->t('WebWash'),
        'content' => $this->t('Learn how to float elements next to each other on a form.'),
        'youtube_id' => 'EgFNqfVboHQ',
      ],
      'webwash_options' => [
        'title' => $this->t('Using Webform in Drupal 8, 2.4: Create List Options'),
        'owner' => $this->t('WebWash'),
        'content' => $this->t('Learn how to create reusable list options for elements.'),
        'youtube_id' => 'magHXd9DNpg',
        'links' => [
          [
            'title' => $this->t('How to Use Webform Predefined Options in Drupal 8 | WebWash'),
            'url' => 'https://www.webwash.net/use-webform-predefined-options-drupal-8/',
          ],
        ],
      ],
      'webwash_email' => [
        'title' => $this->t('Using Webform in Drupal 8, 2.5: Sending Emails'),
        'owner' => $this->t('WebWash'),
        'content' => $this->t('Learn how to send emails when a submission is submitted.'),
        'youtube_id' => 'kSzi1J1MyBc',
      ],
      'webwash_confirmation' => [
        'title' => $this->t('Using Webform in Drupal 8, 2.6: Create Confirmation Page'),
        'owner' => $this->t('WebWash'),
        'content' => $this->t('Learn how to create a custom confirmation page.'),
        'youtube_id' => 'edYCWGoLzZk',
      ],
      'webwash_submissions' => [
        'title' => $this->t('Using Webform in Drupal 8, 3.1: View, Download and Clear Submissions'),
        'owner' => $this->t('WebWash'),
        'content' => $this->t('Learn how to view and manage submission data.'),
        'youtube_id' => 'dftBF8P4Lh4',
      ],
      'webwash_drafts' => [
        'title' => $this->t('Using Webform in Drupal 8, 3.2: Allow Draft Submissions'),
        'owner' => $this->t('WebWash'),
        'content' => $this->t('Learn how to allow users to save draft submissions.'),
        'youtube_id' => 'xA3RtJFZc_4',
      ],
      'webwash_zapier' => [
        'title' => $this->t('Using Webform in Drupal 8, 4.1: Send Submissions to Zapier'),
        'owner' => $this->t('WebWash'),
        'content' => $this->t('Learn how to integrate Webform with other system using Zapier.'),
        'youtube_id' => 'GY0F-rya2iY',
        'links' => [
          [
            'title' => $this->t('Integrate Webform and Google Sheets using Zapier in Drupal 8 | WebWash'),
            'url' => 'https://www.webwash.net/integrate-webform-and-google-sheets-using-zapier-in-drupal-8/',
          ],
        ],
      ],
      'webwash_block' => [
        'title' => $this->t('Using Webform in Drupal 8, 5.1: Display Form as a Block'),
        'owner' => $this->t('WebWash'),
        'content' => $this->t('Learn how to display a form as a block.'),
        'youtube_id' => 'men4peeDS_4',
      ],
      'webwash_node' => [
        'title' => $this->t('Using Webform in Drupal 8, 5.2: Display Form using Webform Node'),
        'owner' => $this->t('WebWash'),
        'content' => $this->t('Learn how to display forms using Webform Node sub-module.'),
        'youtube_id' => '29pntXdy81k',
      ],
      'webwash_conditional_pattern' => [
        'title' => $this->t('Using Pattern Trigger (Regex) in Webform Conditional Logic in Drupal 8'),
        'owner' => $this->t('WebWash'),
        'content' => $this->t('Learn how to use regular expressions with conditional logic.'),
        'youtube_id' => 'JyZXL8zoJ60',
        'links' => [
          [
            'title' => $this->t('Using Pattern Trigger (Regex) in Webform Conditional Logic in Drupal 8 | WebWash'),
            'url' => 'https://www.webwash.net/using-pattern-trigger-regex-webform-conditional-logic-drupal/',
          ],
        ],
      ],
      'webwash_taxonomy_terms' => [
        'title' => $this->t('Use Taxonomy Terms as Webform Options in Drupal 8'),
        'owner' => $this->t('WebWash'),
        'content' => $this->t('Learn how to create a select element which uses a taxonomy vocabulary instead of the standard options.'),
        'youtube_id' => 'hAqbYDm5EDg',
        'links' => [
          [
            'title' => $this->t('Use Taxonomy Terms as Webform Options in Drupal 8 | WebWash'),
            'url' => 'https://www.webwash.net/taxonomy-terms-as-webform-options-in-drupal/',
          ],
        ],
      ],
    ];
    foreach ($videos as $id => &$video_info) {
      $video_info['id'] = $id;
    }

    return $videos;
  }

  /****************************************************************************/
  // Help.
  /****************************************************************************/

  /**
   * Initialize help.
   *
   * @return array
   *   An associative array containing help.
   */
  protected function initHelp() {
    $help = [];

    /**************************************************************************/
    // Notifications.
    /**************************************************************************/

    if ($this->currentUser->hasPermission('administer webform')) {
      $notifications = $this->getNotifications();
      foreach ($notifications as $type => $messages) {
        foreach ($messages as $id => $message) {
          $message_id = 'webform_help_notification__' . $id;
          $help['webform_help_notification__' . $id] = [
            'group' => 'notifications',
            'content' => $message,
            'message_id' => $message_id,
            'message_type' => $type,
            'message_close' => TRUE,
            'message_storage' => WebformMessage::STORAGE_CUSTOM,
            'routes' => [
              // @see /admin/structure/webform
              'entity.webform.collection',
            ],
          ];
        }
      }
    }

    /**************************************************************************/
    // Promotions.
    // Disable promotions via Webform admin settings.
    // (/admin/structure/webform/config/advanced).
    /**************************************************************************/

    // Promotions: Webform.
    $t_args = [
      ':href_involved' => 'https://www.drupal.org/getting-involved',
      ':href_association' => 'https://www.drupal.org/association/?utm_source=webform&utm_medium=referral&utm_campaign=membership-webform-2019-06-06 ',
      ':href_opencollective' => 'https://opencollective.com/webform',
    ];
    $help['promotion_webform'] = [
      'group' => 'promotions',
      'title' => $this->t('Promotions: Drupal Association'),
      'content' => $this->t('If you enjoy and value Drupal and the Webform module, <a href=":href_involved">get involved</a>, consider <a href=":href_association">joining the Drupal Association</a>, and <a href=":href_opencollective">backing the Webform module\'s Open Collective</a>.', $t_args),
      'message_type' => 'webform',
      'message_close' => TRUE,
      'message_storage' => WebformMessage::STORAGE_STATE,
      'attached' => ['library' => ['webform/webform.promotions']],
      'access' => $this->currentUser->hasPermission('administer webform')
        && !$this->configFactory->get('webform.settings')->get('ui.promotions_disabled'),
      'reset_version' => TRUE,
      'routes' => [
        // @see /admin/structure/webform
        'entity.webform.collection',
      ],
    ];

    /**************************************************************************/
    // Installation.
    /**************************************************************************/

    // Installation.
    $t_args = [
      ':about_href' => 'https://www.drupal.org/docs/8/modules/webform',
      ':addons_href' => Url::fromRoute('webform.addons')->toString(),
      ':submodules_href' => Url::fromRoute('system.modules_list', [], ['fragment' => 'edit-modules-webform'])->toString(),
      ':libraries_href' => Url::fromRoute('webform.config.libraries')->toString(),
    ];
    $help['installation'] = [
      'group' => 'installation',
      'title' => $this->t('Installation'),
      'content' => '<strong>' . $this->t('Congratulations!') . '</strong> ' .
        $this->t('You have successfully installed the Webform module.') .
        ' ' . $this->t('Learn more about the <a href=":about_href">Webform module and Drupal</a>', $t_args) . '</br>' .
        $this->t('Please make sure to install additional <a href=":libraries_href">third-party libraries</a>, <a href=":submodules_href">sub-modules</a> and optional <a href=":addons_href">add-ons</a>.', $t_args),
      'video_id' => 'installation',
      'message_type' => 'webform',
      'message_close' => TRUE,
      'message_storage' => WebformMessage::STORAGE_STATE,
      'access' => $this->currentUser->hasPermission('administer webform'),
      'attached' => ['library' => ['webform/webform.promotions']],
      'routes' => [
        // @see /admin/modules
        'system.modules_list',
      ],
    ];

    /**************************************************************************/
    // Forms.
    /**************************************************************************/

    // Webforms.
    $help['webforms_manage'] = [
      'group' => 'forms',
      'title' => $this->t('Forms'),
      'content' => $this->t('The <strong>Forms</strong> management page lists all available webforms, which can be filtered by the following: title, description, elements, category and status.'),
      'video_id' => 'forms',
      'routes' => [
        // @see /admin/structure/webform
        'entity.webform.collection',
      ],
    ];

    /**************************************************************************/
    // Addons.
    /**************************************************************************/

    // Addons.
    $help['addons'] = [
      'group' => 'addons',
      'title' => $this->t('Add-ons'),
      'content' => $this->t('The <strong>Add-ons</strong> page lists Drupal modules and projects that extend and provide additional functionality to the Webform module and Drupal\'s Form API.  If you would like a module or project to be included in the below list, please submit a request to the <a href=":href">Webform module\'s issue queue</a>.', [':href' => 'https://www.drupal.org/node/add/project-issue/webform']),
      'video_id' => 'addons',
      'routes' => [
        // @see /admin/structure/webform/addons
        'webform.addons',
      ],
    ];


    /**************************************************************************/
    // Help.
    /**************************************************************************/

    $help['help'] = [
      'group' => 'help',
      'title' => $this->t('Help'),
      'content' => $this->t('Visit the Webform 8.x-5.x <a href="https://www.drupal.org/node/2856146">documentation pages</a> for an <a href="https://www.drupal.org/node/2834423">introduction</a>, <a href="https://www.drupal.org/node/2837024">features overview</a>, <a href="https://www.drupal.org/node/2932764">articles</a>, <a href="https://www.drupal.org/node/2860989">recipes</a>, <a href="https://www.drupal.org/node/2932760">known issues</a>, and a <a href="https://www.drupal.org/node/2843422">roadmap</a>.'),
      'routes' => [
        // @see /admin/structure/webform/help
        'webform.help',
      ],
    ];

    /**************************************************************************/
    // Configuration.
    /**************************************************************************/

    // Configuration: Forms.
    $help['config_forms'] = [
      'group' => 'configuration',
      'title' => $this->t('Configuration: Forms'),
      'content' => $this->t('The <strong>Forms configuration</strong> page allows administrators to manage form settings, behaviors, labels, messages and CSS classes.'),
      'video_id' => 'configuration',
      'routes' => [
        // @see /admin/structure/webform/config/forms
        'webform.config',
      ],
    ];

    // Configuration: Elements.
    $help['config_elements'] = [
      'group' => 'configuration',
      'title' => $this->t('Configuration: Elements'),
      'content' => $this->t('The <strong>Elements configuration</strong> page allows administrators to enable/disable element types and manage element specific settings, properties, behaviors and formatting.'),
      'video_id' => 'configuration',
      'routes' => [
        // @see /admin/structure/webform/config/element
        'webform.config.elements',
      ],
    ];

    // Configuration: Options.
    $help['config_options'] = [
      'group' => 'configuration',
      'title' => $this->t('Configuration: Options'),
      'content' => $this->t('The <strong>Options configuration</strong> page lists reusable predefined options/values available for select menus, radio buttons, checkboxes and Likert elements.'),
      'video_id' => 'configuration',
      'routes' => [
        // @see /admin/structure/webform/config/options
        'entity.webform_options.collection',
      ],
    ];

    // Configuration: Submissions.
    $help['config_submissions'] = [
      'group' => 'configuration',
      'title' => $this->t('Configuration: Submissions'),
      'content' => $this->t('The <strong>Submissions configuration</strong> page allows administrators to manage submissions settings, behaviors and messages.'),
      'video_id' => 'configuration',
      'routes' => [
        // @see /admin/structure/webform/config/submissions
        'webform.config.submissions',
      ],
    ];

    // Configuration: Handlers.
    $help['config_handlers'] = [
      'group' => 'configuration',
      'title' => $this->t('Configuration: Handlers'),
      'content' => $this->t('The <strong>Handlers configuration</strong> page allows administrators to enable/disable handlers and configure default email settings and messages.') . ' ' .
        $this->t('<strong>Handlers</strong> are used to route submitted data to external applications and send notifications & confirmations.'),
      'video_id' => 'configuration',
      'routes' => [
        // @see /admin/structure/webform/config/handlers
        'webform.config.handlers',
      ],
    ];

    // Configuration: Variants.
    $help['config_variants'] = [
      'group' => 'configuration',
      'title' => $this->t('Configuration: Variants'),
      'content' => $this->t('The <strong>Variants configuration</strong> page allows administrators to enable/disable variants.') . ' ' .
        $this->t('<strong>Variants</strong> are used for A/B testing, segmentation, and personalization.'),
      'video_id' => 'configuration',
      'routes' => [
        // @see /admin/structure/webform/config/variants
        'webform.config.variants',
      ],
    ];

    // Configuration: Exporters.
    $help['config_exporters'] = [
      'group' => 'configuration',
      'title' => $this->t('Configuration: Exporters'),
      'content' => $this->t('The <strong>Exporters configuration</strong> page allows administrators to enable/disable exporters and configure default export settings.') . ' ' .
        $this->t('<strong>Exporters</strong> are used to export results into a downloadable format that can be used by MS Excel, Google Sheets and other spreadsheet applications.'),
      'video_id' => 'configuration',
      'routes' => [
        // @see /admin/structure/webform/config/exporters
        'webform.config.exporters',
      ],
    ];

    // Configuration: Libraries.
    $help['config_libraries'] = [
      'group' => 'configuration',
      'title' => $this->t('Configuration: Libraries'),
      'content' => $this->t('The <strong>Libraries configuration</strong> page allows administrators to enable/disable libraries and define global custom CSS/JavaScript used by all webforms.'),
      'video_id' => 'configuration',
      'routes' => [
        // @see /admin/structure/webform/config/libraries
        'webform.config.libraries',
      ],
    ];

    // Configuration: Libraries.
    $t_args = [
      '@webform-libraries-make' => 'webform-libraries-make',
      '@webform-libraries-composer' => 'webform-libraries-composer',
      '@webform-libraries-download' => 'webform-libraries-download',
      '@webform-composer-update' => 'webform-composer-update',
    ];
    $drush_version = (class_exists('\Drush\Drush')) ? \Drush\Drush::getMajorVersion() : 8;
    if ($drush_version >= 9) {
      foreach ($t_args as $command_name => $command) {
        $t_args[$command_name] = str_replace('-', ':', $command);
      }
    }
    $help['config_libraries_help'] = [
      'group' => 'configuration',
      'title' => $this->t('Configuration: Libraries: Help'),
      'content' => '<p>' . $this->t('The Webform module utilizes third-party Open Source libraries to enhance webform elements and to provide additional functionality.') . ' ' .
        $this->t('It is recommended that these libraries are installed in your Drupal installations /libraries or /web/libraries directory.') . ' ' .
        $this->t('If these libraries are not installed, they will be automatically loaded from a CDN.') . ' ' .
        $this->t('All libraries are optional and can be excluded via the admin settings form.') .
        '</p>' .
        '<p>' . $this->t('There are several ways to download the needed third-party libraries.') . '</p>' .
        '<p><strong>' . $this->t('Recommended') . '</strong></p>' .
        '<ul>' .
        '<li>' . $this->t('Use the <a href="https://github.com/wikimedia/composer-merge-plugin">Composer Merge plugin</a> to include the Webform module\'s <a href="https://cgit.drupalcode.org/webform/tree/composer.libraries.json">composer.libraries.json</a> or generate a custom file using <code>drush @webform-libraries-composer &gt; DRUPAL_ROOT/composer.libraries.json</code>.', $t_args) . '<br/><strong>' . t('<a href="https://www.drupal.org/node/3003140">Learn more &raquo;</a>') . '</strong>'. '</li>' .
        '</ul>' .
        '<p><strong>' . $this->t('Alternatives') . '</strong></p>' .
        '<ul>' .
        '<li>' . $this->t('Generate a *.make.yml or composer.json file using <code>drush @webform-libraries-make</code> or <code>drush @webform-libraries-composer</code>.', $t_args) . '</li>' .
        '<li>' . $this->t('Execute <code>drush @webform-libraries-download</code>, to download third-party libraries required by the Webform module. (OSX/Linux)', $t_args) . '</li>' .
        '<li>' . $this->t("Execute <code>drush @webform-composer-update</code>, to update your Drupal installation's composer.json to include the Webform module's selected libraries as repositories.", $t_args) . '</li>' .
        '<li>' . $this->t('Download and extract a <a href=":href">zipped archive containing all webform libraries</a> and extract the directories and files to /libraries or /web/libraries', [':href' => 'https://git.drupalcode.org/sandbox/jrockowitz-2941983/raw/8.x-1.x/libraries.zip']) . '</li>' .
        '</ul>',
      'message_type' => 'info',
      'message_close' => TRUE,
      'message_storage' => WebformMessage::STORAGE_SESSION,
      'routes' => [
        // @see /admin/structure/webform/config/libraries
        'webform.config.libraries',
      ],
    ];

    // Configuration: Advanced.
    $help['config_advanced'] = [
      'group' => 'configuration',
      'title' => $this->t('Configuration: Advanced'),
      'content' => $this->t('The <strong>Advanced configuration</strong> page allows an administrator to enable/disable UI behaviors, manage requirements and define data used for testing webforms.'),
      'video_id' => 'configuration',
      'routes' => [
        // @see /admin/structure/webform/config/advanced
        'webform.config.advanced',
      ],
    ];

    // Configuration: Translate.
    $help['config_translation'] = [
      'group' => 'configuration',
      'title' => $this->t('Configuration: Translate'),
      'content' => $this->t('The <strong>Translate configuration</strong> page allows webform messages and labels to be translated into multiple languages.'),
      'video_id' => 'translations',
      'routes' => [
        // /admin/structure/webform/config/translate
        'config_translation.item.overview.webform.config',
      ],
    ];

    /**************************************************************************/
    // Plugins.
    /**************************************************************************/

    // Plugins: Elements.
    $help['plugins_elements'] = [
      'group' => 'plugins',
      'title' => $this->t('Plugins: Elements'),
      'content' => $this->t('The <strong>Element plugins</strong> overview page lists all available webform element plugins.') . ' ' .
        $this->t('<strong>Webform Element</strong> plugins are used to enhance existing render/form elements. Webform element plugins provide default properties, data normalization, custom validation, element configuration form and customizable display formats.'),
      'video_id' => 'plugins',
      'routes' => [
        // @see /admin/reports/webform-plugins/elements
        'webform.reports_plugins.elements',
      ],
    ];

    // Plugins: Handlers.
    $help['plugins_handlers'] = [
      'group' => 'plugins',
      'title' => $this->t('Plugins: Emails/Handlers'),
      'content' => $this->t('The <strong>Handler plugins</strong> overview page lists all available webform handler plugins.') . ' ' .
        $this->t('<strong>Handlers</strong> are used to route submitted data to external applications and send notifications & confirmations.'),
      'video_id' => 'plugins',
      'routes' => [
        // @see /admin/reports/webform-plugins/handlers
        'webform.reports_plugins.handlers',
      ],
    ];

    // Plugins: Variants.
    $help['plugins_variants'] = [
      'group' => 'plugins',
      'title' => $this->t('Plugins: Variants'),
      'content' => $this->t('The <strong>Variant plugins</strong> overview page lists all available webform variant plugins.') . ' ' .
        $this->t('<strong>Variants</strong> are used for A/B testing, segmentation, and personalization.'),
      'video_id' => 'plugins',
      'routes' => [
        // @see /admin/reports/webform-plugins/variants
        'webform.reports_plugins.variants',
      ],
    ];
    // Plugins: Exporters.
    $help['plugins_exporters'] = [
      'group' => 'plugins',
      'title' => $this->t('Plugins: Exporters'),
      'content' => $this->t('The <strong>Exporter plugins</strong> overview page lists all available results exporter plugins.') . ' ' .
        $this->t('<strong>Exporters</strong> are used to export results into a downloadable format that can be used by MS Excel, Google Sheets and other spreadsheet applications.'),
      'video_id' => 'plugins',
      'routes' => [
        // @see /admin/reports/webform-plugins/exporters
        'webform.reports_plugins.exporters',
      ],
    ];

    /**************************************************************************/
    // Webform.
    /**************************************************************************/

    // Webform: Source.
    $help['webform_source'] = [
      'group' => 'forms',
      'title' => $this->t('Webform: Source'),
      'content' => $this->t("The (View) <strong>Source</strong> page allows developers to edit a webform's render array using YAML markup.") . ' ' .
        $this->t("Developers can use the (View) <strong>Source</strong> page to alter a webform's labels quickly, cut-n-paste multiple elements, reorder elements, as well as  add custom properties and markup to elements."),
      'video_id' => 'forms',
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/source
        'entity.webform.source_form',
      ],
    ];

    // Webform: Test.
    $help['webform_test'] = [
      'group' => 'forms',
      'title' => $this->t('Webform: Test'),
      'content' => $this->t("The <strong>Test</strong> form allows a webform to be tested using a customizable test dataset.") . ' ' .
        $this->t('Multiple test submissions can be created using the Devel generate module.'),
      'video_id' => 'forms',
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/test
        'entity.webform.test_form',
        // @see /node/{node}/webform/test
        'entity.node.webform.test_form',
      ],
    ];

    // Webform: API.
    $help['webform_api'] = [
      'group' => 'forms',
      'title' => $this->t('Webform: API'),
      'content' => $this->t("The <strong>API</strong> form allows developers to test a webform's API."),
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/api
        'entity.webform.api_form',
        // @see /node/{node}/webform/api
        'entity.node.webform.api_form',
      ],
    ];

    // Webform: Translations.
    $help['webform_translate'] = [
      'group' => 'translations',
      'title' => $this->t('Webform: Translate'),
      'content' => $this->t("The <strong>Translate</strong> page allows a webform's configuration and elements to be translated into multiple languages."),
      'video_id' => 'translations',
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/translate
        'entity.webform.config_translation_overview',
      ],
    ];

    /**************************************************************************/
    // Elements.
    /**************************************************************************/

    // Elements.
    $help['elements'] = [
      'group' => 'elements',
      'title' => $this->t('Elements'),
      'content' => $this->t('The <strong>Elements</strong>  page allows users to add, update, duplicate and delete elements and wizard pages.'),
      'video_id' => 'elements',
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}
        'entity.webform.edit_form',
      ],
    ];

    /**************************************************************************/
    // Handlers.
    /**************************************************************************/

    // Handlers.
    $help['handlers'] = [
      'group' => 'handlers',
      'title' => $this->t('Handlers'),
      'content' => $this->t('The <strong>Emails/Handlers</strong> page allows additional actions and behaviors to be processed when a webform or submission is created, updated, or deleted.') . ' ' .
        $this->t('<strong>Handlers</strong> are used to route submitted data to external applications and send notifications & confirmations.'),
      'video_id' => 'submissions',
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/handlers
        'entity.webform.handlers',
      ],
    ];

    /**************************************************************************/
    // Variants.
    /**************************************************************************/

    // Variants.
    $help['variants'] = [
      'group' => 'variants',
      'title' => $this->t('Variants'),
      'content' => $this->t('The <strong>Variants</strong> page allows variations of a webform to be created and managed for A/B testing, segmentation, and personalization.'),
      'video_id' => 'variants',
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/variants
        'entity.webform.variants',
      ],
    ];

    /**************************************************************************/
    // Settings.
    /**************************************************************************/

    // Settings.
    $help['settings'] = [
      'group' => 'settings',
      'title' => $this->t('Settings: General'),
      'content' => $this->t("The <strong>General</strong> settings page allows a webform's administrative information, paths, behaviors and third-party settings to be customized."),
      'video_id' => 'settings',
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/settings
        'entity.webform.settings',
      ],
    ];

    // Settings: Form.
    $help['settings_form'] = [
      'group' => 'settings',
      'title' => $this->t('Settings: Form'),
      'content' => $this->t("The <strong>Form</strong> settings page allows a webform's status, attributes, behaviors, labels, messages, wizard settings and preview to be customized."),
      'video_id' => 'settings',
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/settings/form
        'entity.webform.settings_form',
      ],
    ];

    // Settings: Submissions.
    $help['settings_submissions'] = [
      'group' => 'settings',
      'title' => $this->t('Settings: Submissions'),
      'content' => $this->t("The <strong>Submissions</strong> settings page allows a submission's labels, behaviors, limits and draft settings to be customized."),
      'video_id' => 'settings',
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/settings/submissions
        'entity.webform.settings_submissions',
      ],
    ];

    // Settings: Confirmation.
    $help['settings_confirmation'] = [
      'group' => 'settings',
      'title' => $this->t('Settings: Confirmation'),
      'content' => $this->t("The <strong>Confirmation</strong> settings page allows the submission confirmation type, message and URL to be customized."),
      'video_id' => 'settings',
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/settings/confirmation
        'entity.webform.settings_confirmation',
      ],
    ];

    // Settings: Assets.
    $help['settings_assets'] = [
      'group' => 'settings',
      'title' => $this->t('Settings: Assets'),
      'content' => $this->t("The <strong>CSS/JS</strong> settings page allows site builders to attach custom CSS and JavaScript to a webform."),
      'video_id' => 'settings',
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/settings/assets
        'entity.webform.settings_assets',
      ],
    ];

    // Settings: Access.
    $help['settings_access'] = [
      'group' => 'settings',
      'title' => $this->t('Settings: Access'),
      'content' => $this->t('The <strong>Access</strong> settings page allows an administrator to determine who can administer a webform and/or create, update, delete and purge webform submissions.'),
      'video_id' => 'settings',
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/access
        'entity.webform.settings_access',
      ],
    ];

    /**************************************************************************/
    // Submissions/Results.
    /**************************************************************************/

    // Submissions.
    $help['submissions'] = [
      'group' => 'submissions',
      'title' => $this->t('Submissions'),
      'content' => $this->t('The <strong>Submissions</strong> page lists all incoming submissions for all webforms.'),
      'routes' => [
        // @see /admin/structure/webform/submissions/manage
        'entity.webform_submission.collection',
      ],
    ];

    // Results.
    $help['results'] = [
      'group' => 'submissions',
      'title' => $this->t('Results: Submissions'),
      'content' => $this->t("The <strong>Submissions</strong> page displays a customizable overview of a webform's submissions.") . ' ' .
        $this->t("Submissions can be reviewed, updated, flagged and/or annotated."),
      'video_id' => 'submissions',
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/results/submissions
        'entity.webform.results_submissions',
      ],
    ];

    // Results: Download.
    $help['results_download'] = [
      'group' => 'submissions',
      'title' => $this->t('Results: Download'),
      'content' => $this->t("The <strong>Download</strong> page allows a webform's submissions to be exported into a customizable CSV (Comma Separated Values) file and other common data formats."),
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/results/download
        'entity.webform.results_export',
      ],
    ];

    /**************************************************************************/
    // Submission.
    /**************************************************************************/

    $help['submission'] = [
      'group' => 'submission',
      'title' => $this->t('Submission: View'),
      'content' => $this->t("The <strong>View</strong> page displays a submission's general information and data."),
      'video_id' => 'submission',
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/submission/{webform_submisssion}
        'entity.webform_submission.canonical',
        // @see /node/{node}/webform/submission/{webform_submisssion}
        'entity.node.webform_submission.canonical',
      ],
    ];

    $help['submission_table'] = [
      'group' => 'submission',
      'title' => $this->t('Submission: Table'),
      'content' => $this->t("The <strong>Table</strong> page displays a submission's general information and data using tabular layout."),
      'video_id' => 'submission',
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/submission/{webform_submisssion}/table
        'entity.webform_submission.table',
        // @see /node/{node}/webform/submission/{webform_submisssion}/table
        'entity.node.webform_submission.table',
      ],
    ];

    $help['submission_text'] = [
      'group' => 'submission',
      'title' => $this->t('Submission: Plain text'),
      'content' => $this->t("The <strong>Plain text</strong> page displays a submission's general information and data as plain text."),
      'video_id' => 'submission',
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/submission/{webform_submisssion}/text
        'entity.webform_submission.text',
        // @see /node/{node}/webform/submission/{webform_submisssion}/text
        'entity.node.webform_submission.text',
      ],
    ];

    $help['submission_yaml'] = [
      'group' => 'submission',
      'title' => $this->t('Submission: Data (YAML)'),
      'content' => $this->t("The <strong>Data (YAML)</strong> page displays a submission's raw data as YAML."),
      'video_id' => 'submission',
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/submission/{webform_submisssion}/yaml
        'entity.webform_submission.yaml',
        // @see /node/{node}/webform/submission/{webform_submisssion}/yaml
        'entity.node.webform_submission.yaml',
      ],
    ];

    $help['submission_edit'] = [
      'group' => 'submission',
      'title' => $this->t('Submission: Edit'),
      'content' => $this->t("The <strong>Edit</strong> form allows the administrator to update a submission."),
      'video_id' => 'submission',
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/submission/{webform_submisssion}/edit
        'entity.webform_submission.edit_form',
        // @see /node/{node}/webform/submission/{webform_submisssion}/edit
        'entity.node.webform_submission.edit_form',
      ],
    ];

    $help['submission_edit_all'] = [
      'group' => 'submission',
      'title' => $this->t('Submission: Edit All'),
      'content' => $this->t("The <strong>Edit all</strong> form allows administrator to update all values for submission create from a multi-step form."),
      'video_id' => 'submission',
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/submission/{webform_submisssion}/edit_all
        'entity.webform_submission.edit_all',
        // @see /node/{node}/webform/submission/{webform_submisssion}/edit_all
        'entity.node.webform_submission.edit_all',
      ],
    ];

    $help['submission_resend'] = [
      'group' => 'submission',
      'title' => $this->t('Submission: Resend'),
      'content' => $this->t("The <strong>Resend</strong> form allows administrator to preview and resend emails and messages."),
      'video_id' => 'submission',
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/submission/{webform_submisssion}/resend
        'entity.webform_submission.resend_form',
        // @see /node/{node}/webform/submission/{webform_submisssion}/resend
        'entity.node.webform_submission.resend_form',
      ],
    ];

    $help['submission_notes'] = [
      'group' => 'submission',
      'title' => $this->t('Submission: Notes'),
      'content' => $this->t("The <strong>Notes</strong> form allows administrator to flag and annotate a submission."),
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/submission/{webform_submisssion}/notes
        'entity.webform_submission.notes_form',
        // @see /node/{node}/webform/submission/{webform_submisssion}/notes
        'entity.node.webform_submission.notes_form',
      ],
    ];

    /**************************************************************************/
    // Export.
    /**************************************************************************/

    // Export: Config.
    $config_import_href = ($this->moduleHandler->moduleExists('config') && $this->currentUser->hasPermission('import configuration'))
      ? Url::fromRoute('config.import_single', [], ['query' => ['config_type' => 'webform']])->toString()
      : 'https://www.drupal.org/docs/8/configuration-management';
    $help['webform_config_export'] = [
      'group' => 'development',
      'title' => $this->t('Devel: Export'),
      'content' => $this->t("The <strong>Config Export</strong> form allows developers to quickly export a single webform's YAML configuration file.")
        . ' ' . $this->t('A single webform\'s YAML configuration file can easily be <a href=":href">imported</a> into another Drupal instance.', [':href' => $config_import_href])
        . ' ' . $this->t('If you run into any issues with a webform, you can also attach the below configuration (without any personal information) to a new ticket in the Webform module\'s <a href=":href">issue queue</a>.', [':href' => 'https://www.drupal.org/project/issues/webform']),
      'video_id' => 'development',
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/export
        'entity.webform.export_form',
      ],
    ];

    /**************************************************************************/
    // Modules.
    /**************************************************************************/


    // Webform Entity Print (PDF).
    $help['webform_entity_print'] = [
      'group' => 'webform_entity_print',
      'title' => $this->t('Webform Entity Print (PDF)'),
      'content' => $this->t('Provides <a href=":href">Entity Print</a> (PDF) integration and allows site builders to download, export, and email PDF copies of webform submissions.', [':href' => 'https://www.drupal.org/project/entity_print']),
      'video_id' => 'print',
    ];

    // Webform Node.
    $help['webform_node'] = [
      'group' => 'webform_nodes',
      'title' => $this->t('Webform Node'),
      'content' => $this->t("A <strong>Webform Node</strong> allows webforms to be fully integrated into a website as nodes."),
      'video_id' => 'webform_nodes',
      'paths' => [
        '/node/add/webform',
      ],
    ];
    $help['webform_node_reference'] = [
      'group' => 'webform_nodes',
      'title' => $this->t('Webform Node: References'),
      'content' => $this->t("The <strong>Reference</strong> pages displays an overview of a webform's references and allows you to quickly create new references (a.k.a Webform nodes)."),
      'video_id' => 'webform_nodes',
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/references
        'entity.webform.references',
      ],
    ];
    $help['webform_node_results'] = [
      'group' => 'webform_nodes',
      'title' => $this->t('Webform Node: Results: Submissions'),
      'content' => $this->t("The <strong>Submissions</strong> page displays a customizable overview of a webform node's submissions.") . ' ' .
        $this->t("Submissions can be reviewed, updated, flagged and annotated."),
      'video_id' => 'webform_nodes',
      'routes' => [
        // @see /node/{node}/webform/results/submissions
        'entity.node.webform.results_submissions',
      ],
    ];
    $help['webform_node_results_download'] = [
      'group' => 'webform_nodes',
      'title' => $this->t('Webform Node: Results: Download'),
      'content' => $this->t("The <strong>Download</strong> page allows a webform node's submissions to be exported into a customizable CSV (Comma Separated Values) file and other common data formats."),
      'routes' => [
        // @see /node/{node}/webform/results/download
        'entity.node.webform.results_export',
      ],
    ];

    // Webform Block.
    $help['webform_block'] = [
      'group' => 'webform_blocks',
      'title' => $this->t('Webform Block'),
      'content' => $this->t("A <strong>Webform Block</strong> allows a webform to be placed anywhere on a website."),
      'video_id' => 'webform_blocks',
      'paths' => [
        '/admin/structure/block/add/webform_block/*',
      ],
    ];

    // Webform Accessibility.
    $help['webform_accessibility'] = [
      'group' => 'webform_accessibility',
      'title' => $this->t('Webform Node'),
      'content' => $this->t("The Webform module aims to be accessible to all users."),
      'video_id' => 'accessibility',
      'paths' => [
        '/admin/structure/webform/manage/example_accessibility_*',
      ],
      'message_type' => 'info',
      'message_close' => TRUE,
      'message_storage' => WebformMessage::STORAGE_USER,
      'access' => $this->currentUser->hasPermission('administer webform'),
      'weight' => -10,
    ];

    /**************************************************************************/
    // Messages.
    /**************************************************************************/

    // Webform: Elements -- Warning.
    $help['message_webform_ui'] = [
      'group' => 'messages',
      'title' => $this->t('Message: Webform UI Disabled'),
      'content' => $this->t('Please enable the <strong>Webform UI</strong> module if you would like to add easily add and manage elements using a drag-n-drop user interface.'),
      'message_type' => 'warning',
      'message_close' => TRUE,
      'message_storage' => WebformMessage::STORAGE_STATE,
      'access' => $this->currentUser->hasPermission('administer webform')
        && $this->currentUser->hasPermission('administer modules')
        && !$this->moduleHandler->moduleExists('webform_ui')
        && !$this->moduleHandler->moduleExists('webform_editorial'),
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}
        'entity.webform.edit_form',
      ],
    ];

    // Let other modules provide any extra help.
    $help += $this->moduleHandler->invokeAll('webform_help_info');
    $this->moduleHandler->alter('webform_help_info', $help);

    /**************************************************************************/

    // Initialize help.
    foreach ($help as $id => &$help_info) {
      $help_info += [
        'id' => $id,
        'reset_version' => FALSE,
      ];
    }

    // Reset storage state if the Webform module version has changed.
    if ($this->isUpdated()) {
      foreach ($help as $id => $help_info) {
        if (!empty($help_info['reset_version'])) {
          WebformMessage::resetClosed(WebformMessage::STORAGE_STATE, 'webform.help.' . $id);
        }
      }
      $this->state->set('webform.version', $this->getVersion());
    }

    return $help;
  }

}
