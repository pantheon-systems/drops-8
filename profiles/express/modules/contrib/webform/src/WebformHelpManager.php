<?php

namespace Drupal\webform;

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
  public function buildHelp($route_name, RouteMatchInterface $route_match) {
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
    // return $this->buildAddons();
    // return $this->buildLibraries();
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
    if ($docs) {
      $video_display = 'documentation';
    }
    if ($video_display == 'none') {
      return [];
    }
    $classes = ['button', 'button-action', 'button--small', 'button-webform-play'];

    $rows = [];
    foreach ($this->videos as $id => $video) {
      if (!empty($video['hidden'])) {
        continue;
      }

      switch ($video_display) {
        case 'dialog':
          $url = Url::fromRoute('webform.help.video', ['id' => str_replace('_', '-', $video['id'])]);
          $image_attributes = WebformDialogHelper::getModalDialogAttributes(1000);
          $link_attributes = WebformDialogHelper::getModalDialogAttributes(1000, $classes);
          break;

        case 'link':
          $url = Url::fromUri('https://youtu.be/' . $video['youtube_id']);
          $image_attributes = [];
          $link_attributes = ['class' => $classes];
          break;

        default:
          $url = Url::fromUri('https://youtu.be/' . $video['youtube_id']);
          $image_attributes = [];
          $link_attributes = [];
          break;
      }
      $row = [];

      // Image.
      $row['image'] = [
        'data' => [
          '#type' => 'link',
          '#title' => [
            '#theme' => 'image',
            '#uri' => 'https://img.youtube.com/vi/' . $video['youtube_id'] . '/0.jpg',
            '#alt' => $video['title'],
          ],
          '#url' => $url,
          '#attributes' => $image_attributes,
        ],
        'width' => '200',
      ];
      // Content.
      $row['content'] = ['data' => []];
      $row['content']['data']['title'] = [
        '#markup' => $video['title'],
        '#prefix' => '<h3>',
        '#suffix' => '</h3>',
      ];
      $row['content']['data']['content'] = [
        '#markup' => $video['content'],
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ];
      $row['content']['data']['link'] = [
        '#type' => 'link',
        '#title' => $this->t('Watch video'),
        '#url' => $url,
        '#attributes' => $link_attributes,
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ];

      $rows[$id] = ['data' => $row, 'no_striping' => TRUE];
    }

    $build = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#attributes' => [
        'border' => 0,
        'cellpadding' => 2,
        'cellspacing' => 0,
      ]
    ];

    if (!$docs) {
      $build['#header'] = [
        ['data' => '', 'style' => 'padding:0; border-top-color: transparent', 'class' => [RESPONSIVE_PRIORITY_LOW]],
        ['data' => '', 'style' => 'padding:0; border-top-color: transparent'],
      ];
      $build['#attached']['library'][] = 'webform/webform.help';
      $build['#attached']['library'][] = 'webform/webform.ajax';
    }
    else {
      $build['#no_striping'] = TRUE;
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildAddOns($docs = FALSE) {
    $build = [
      'content' => [
        '#markup' => '<p>' . $this->t("Below is a list of modules and projects that extend and/or provide additional functionality to the Webform module and Drupal's Form API.") . '</p>' .
        '<p>★ = ' . $this->t('Recommended') . '</p>',
      ],
    ];

    $categories = $this->addOnsManager->getCategories();
    foreach ($categories as $category_name => $category) {
      $build['content'][$category_name]['title'] = [
        '#markup' => $category['title'],
        '#prefix' => '<h3>',
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
            '#title' => $project['title'],
            '#url' => $project['url'],
            '#prefix' => '<dt>',
            '#suffix' => ((isset($project['recommended'])) ? ' ★': '') . '</dt>',
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
            '#suffix' => '<br />'
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
   *   An associative array containing videos.
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
      'devel' => $this->t('Devel'),
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

    $videos['introduction_short'] = [
      'title' => $this->t('Welcome to the Webform module'),
      'content' => $this->t('Welcome to the Webform module for Drupal 8.'),
      'youtube_id' => 'rJ-Hcg5WtSU',
    ];

    $videos['introduction'] = [
      'title' => $this->t('Overview of the Webform module'),
      'content' => $this->t('This screencast provides a complete introduction to the Webform module for Drupal 8.'),
      'youtube_id' => 'AgaD041BTxU',
    ];

    $videos['installing'] = [
      'title' => $this->t('Installing the Webform module and third-party libraries'),
      'content' => $this->t('This screencast walks through installing the core Webform module, sub-module, required libraries and add-ons.'),
      'youtube_id' => 'IMfFTrsjg5k',
    ];

    $videos['forms'] = [
      'title' => $this->t('Managing Forms, Templates, and Examples'),
      'content' => $this->t('This screencast walks through how to view and manage forms, leverage templates, and learn from examples.'),
      'youtube_id' => 'T5MVGa_3jOQ',
    ];

    $videos['elements'] = [
      'title' => $this->t('Adding Elements, Composites, and Containers'),
      'content' => $this->t('This screencast walks through adding elements, composites, and containers to forms.'),
      'youtube_id' => 'LspF9mAvRcY',
    ];

    $videos['settings'] = [
      'title' => $this->t('Configuring Form Settings and Behaviors'),
      'content' => $this->t('This screencast walks through configuring form settings, styles, and behaviors.'),
      'youtube_id' => 'UJ0y09ZS9Uc',
    ];

    $videos['access'] = [
      'title' => $this->t('Controlling Access to Forms and Elements'),
      'content' => $this->t('This screencast walks through how to manage user permissions and controlling access to forms and elements.'),
      'youtube_id' => 'SFm76DAVjbE',
    ];

    $videos['submissions'] = [
      'title' => $this->t('Collecting Submissions, Sending Emails, and Posting Results'),
      'content' => $this->t('This screencast walks through collecting submission, managing results, sending emails, and posting submissions to remote servers.'),
      'youtube_id' => 'OdfVm5LMH9A',
    ];

    $videos['blocks'] = [
      'title' => $this->t('Placing Webforms in Blocks and Creating Webform Nodes'),
      'content' => $this->t('This screencast walks through placing Webform block and creating Webform nodes.'),
      'youtube_id' => 'xYBW2g0osd4',
    ];

    $videos['translate'] = [
      'title' => $this->t('Translating Webforms'),
      'content' => $this->t('This screencast walks through translating a Webform.'),
      'youtube_id' => 'rF8Bd-0w6Cg',
    ];

    $videos['admin'] = [
      'title' => $this->t('Administering and Extending the Webform module'),
      'content' => $this->t("This screencast walks through administering the Webform module's admin settings, options, and behaviors."),
      'youtube_id' => 'bkScAX_Qbt4',
    ];

    $videos['source'] = [
      'title' => $this->t('Using the Source'),
      'content' => $this->t('This screencast walks through viewing and editing source code and configuration behind a Webform.'),
      'youtube_id' => '2pWkJiYeR6E',
    ];

    $videos['help'] = [
      'title' => $this->t('Help Us Help You'),
      'content' => $this->t('This screencast is going to show you how to “help us help you” with issues related to the Webform module.'),
      'youtube_id' => 'uQo-1s2h06E',
    ];

    $videos['promotion_lingotek'] = [
      'title' => $this->t('Webform & Lingotek Partnership'),
      'content' => $this->t('You can help support the Webform module by signing up and trying the Lingotek-Inside Drupal Translation Module for <strong>free</strong>.'),
      'youtube_id' => '83L99vYbaGQ',
      'submit_label' => $this->t('Sign up and try Lingotek'),
      'submit_url' => Url::fromUri('https://lingotek.com/webform'),
      'hidden' => TRUE,
    ];

    $videos['association'] = [
      'title' => $this->t('Join the Drupal Association'),
      'content' => $this->t('The Drupal Association is dedicated to fostering and supporting the Drupal software project, the community and its growth. We help the Drupal community with funding, infrastructure, education, promotion, distribution and online collaboration at Drupal.org.'),
      'youtube_id' => 'LZWqFSMul84',
      'hidden' => TRUE,
    ];

    foreach ($videos as $id => &$video_info) {
      $video_info['id'] = $id;
    }

    return $videos;
  }

  /***************************************************************************/
  // Groups.
  /***************************************************************************/

  /**
   * Initialize video presentations.
   *
   * @return array
   *   An associative array containing videos.
   */
  public function initVideoPresentations() {
    return [
      'introduction' => [
        'title' => $this->t('Introduction to Webform for Drupal 8'),
        'content' => $this->t('This presentation provides a general introduction to the Webform module.'),
        'youtube_id' => '',
      ],
      'about' => [
        'title' => $this->t('About Webform & the Drupal community'),
        'content' => $this->t('This presentation introduces you to the maintainer and community behind the Webform module.'),
        'youtube_id' => '',
      ],
      'installation' => [
        'title' => $this->t('Installing the Webform module'),
        'content' => $this->t('This presentation walks through how to install the Webform and external libraries.'),
        'youtube_id' => '',
      ],
      'forms' => [
        'title' => $this->t('Building forms & templates'),
        'content' => $this->t('This presentation provides an overview of how to create, build, edit and test forms and templates.'),
        'youtube_id' => '',
      ],
      'elements' => [
        'title' => $this->t('Adding elements to a webform'),
        'content' => $this->t('This presentation provides an overview of how to create, configure and manage form elements, layouts and multi-step wizards.'),
        'youtube_id' => '',
      ],
      'handlers' => [
        'title' => $this->t('Emailing & handling submissions'),
        'content' => $this->t('This presentation shows how to route submissions to external applications and send notifications & confirmations.'),
        'youtube_id' => '',
      ],
      'settings' => [
        'title' => $this->t('Configuring webform settings'),
        'content' => $this->t("This presentation shows how to configure a form's general settings, submission handling, confirmation message/page, custom CSS/JS and access controls."),
        'youtube_id' => '',
      ],
      'submissions' => [
        'title' => $this->t('Collecting webform submissions'),
        'content' => $this->t("This presentation shows how to manage, review and export a form's submissions."),
        'youtube_id' => '',
      ],
      'submission' => [
        'title' => $this->t('Understanding a webform submission'),
        'content' => $this->t("This presentation shows how to review, edit, resend and administer a  submission."),
        'youtube_id' => '',
      ],
      'configuration' => [
        'title' => $this->t("Configuring the Webform module"),
        'content' => $this->t('This presentation walks through all the configuration settings available to manage forms, submissions, options, handlers, exporters, libraries and assets.'),
        'youtube_id' => '',
      ],
      'plugins' => [
        'title' => $this->t("Understanding webform plugins"),
        'content' => $this->t("This presentation offers an overview of the Webform module's element, handler and exporter plugins."),
        'youtube_id' => '',
      ],
      'addons' => [
        'title' => $this->t('Extending Webform using add-ons'),
        'content' => $this->t("This presentation suggests and recommends additional Drupal projects that can be installed to enhance, improve and alter the Webform module's functionality."),
        'youtube_id' => '',
      ],
      'webform_nodes' => [
        'title' => $this->t('Attaching webforms to nodes'),
        'content' => $this->t('This presentation walks through how to attach a webform to node.'),
        'youtube_id' => '',
      ],
      'webform_blocks' => [
        'title' => $this->t('Placing webforms as blocks'),
        'content' => $this->t('This presentation walks through how to place a webform on a website as a block.'),
        'youtube_id' => '',
      ],
      'translations' => [
        'title' => $this->t('Translating webforms'),
        'content' => $this->t("This presentation shows how to translate a webform's title, descriptions, label and messages."),
        'youtube_id' => '',
      ],
      'devel' => [
        'title' => $this->t('Webform development tools'),
        'content' => $this->t('This presentation gives developers an overview of the tools available to help build, debug and export forms.'),
        'youtube_id' => '',
      ],
    ];
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
    // Installation.
    /**************************************************************************/

    // Installation.
    $t_args = [
      ':addons_href' => Url::fromRoute('webform.addons')->toString(),
      ':submodules_href' => Url::fromRoute('system.modules_list', [], ['fragment' => 'edit-modules-webform'])->toString(),
      ':libraries_href' => Url::fromRoute('webform.config.libraries')->toString(),
    ];
    $help['installation'] = [
      'group' => 'installation',
      'title' => $this->t('Installation'),
      'content' => $this->t('<strong>Congratulations!</strong> You have successfully installed the Webform module. Please make sure to install additional <a href=":libraries_href">third-party libraries</a>, <a href=":submodules_href">sub-modules</a> and optional <a href=":addons_href">add-ons</a>.', $t_args),
      'video_id' => 'installation',
      'message_type' => 'info',
      'message_close' => TRUE,
      'message_storage' => WebformMessage::STORAGE_STATE,
      'access' => $this->currentUser->hasPermission('administer webform'),
      'uses' => FALSE,
      'routes' => [
        // @see /admin/modules
        'system.modules_list',
      ],
    ];

    /**************************************************************************/
    // Introduction.
    /**************************************************************************/

    // Introduction.
    $help['introduction'] = [
      'group' => 'introduction',
      'title' => $this->t('Introduction'),
      'content' => $this->t('<strong>Welcome to the Webform module for Drupal 8.</strong> The Webform module provides all the features expected from an enterprise proprietary form builder combined with the flexibility and openness of Drupal.'),
      'video_id' => 'introduction',
      'message_type' => 'info',
      'message_close' => TRUE,
      'message_storage' => WebformMessage::STORAGE_USER,
      'access' => $this->currentUser->hasPermission('administer webform'),
      'routes' => [
        // @see /admin/structure/webform
        'entity.webform.collection',
      ],
    ];

    /**************************************************************************/
    // About.
    /**************************************************************************/

    // About: Webform.
    $help['about_webform'] = [
      'group' => 'about',
      'title' => $this->t('About: Webform'),
      'content' => $this->t('The <strong>About Webform</strong> page helps familiarize users with Webform project, issue queue & maintainer.'),
      'video_id' => 'introduction_short',
      'routes' => [
        // @see /admin/structure/webform/about
        'webform.about',
      ],
    ];

    // About: Drupal.
    $help['about_drupal'] = [
      'group' => 'about',
      'title' => $this->t('About: Webform'),
      'content' => $this->t('The <strong>About Drupal</strong> page helps familiarize users with the Drupal community and the Drupal Association.'),
      'video_id' => 'association',
      'routes' => [
        // @see /admin/structure/webform/about/drupal
        'webform.about.drupal',
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

    // Templates.
    if ($this->moduleHandler->moduleExists('webform_templates')) {
      $help['webform_templates'] = [
        'group' => 'forms',
        'title' => $this->t('Templates'),
        'content' => $this->t('The <strong>Templates</strong> page lists reusable templates that can be duplicated and customized to create new webforms.'),
        'video_id' => 'forms',
        'routes' => [
          // @see /admin/structure/webform/templates
          'entity.webform.templates',
        ],
      ];
    }

    /**************************************************************************/
    // Addons.
    /**************************************************************************/

    // Addons.
    $help['addons'] = [
      'group' => 'addons',
      'title' => $this->t('Add-ons'),
      'content' => $this->t('The <strong>Add-ons</strong> page lists Drupal modules and projects that extend and provide additional functionality to the Webform module and Drupal\'s Form API.  If you would like a module or project to be included in the below list, please submit a request to the <a href=":href">Webform module\'s issue queue</a>.', [':href' => 'https://www.drupal.org/node/add/project-issue/webform']),
      'routes' => [
        // @see /admin/structure/webform/addons
        'webform.addons',
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
      'video_id' => 'admin',
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
      'video_id' => 'admin',
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
      'video_id' => 'admin',
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
      'video_id' => 'admin',
      'routes' => [
        // @see /admin/structure/webform/config/submissions
        'webform.config.submissions',
      ],
    ];

    // Configuration: Handlers.
    $help['config_handlers'] = [
      'group' => 'configuration',
      'title' => $this->t('Configuration: Handlers'),
      'content' => $this->t('The <strong>Handlers configuration</strong> page allows administrators to enable/disable handlers and configure default email settings and messages.')  . ' ' .
        $this->t('<strong>Handlers</strong> are used to route submitted data to external applications and send notifications & confirmations.'),
      'video_id' => 'admin',
      'routes' => [
        // @see /admin/structure/webform/config/handlers
        'webform.config.handlers',
      ],
    ];

    // Configuration: Exporters.
    $help['config_exporters'] = [
      'group' => 'configuration',
      'title' => $this->t('Configuration: Exporters'),
      'content' => $this->t('The <strong>Exporters configuration</strong> page allows administrators to enable/disable exporters and configure default export settings.') . ' ' .
        $this->t('<strong>Exporters</strong> are used to export results into a downloadable format that can be used by MS Excel, Google Sheets and other spreadsheet applications.'),
      'video_id' => 'admin',
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
      'video_id' => 'admin',
      'routes' => [
        // @see /admin/structure/webform/config/libraries
        'webform.config.libraries',
      ],
    ];

    // Configuration: Libraries.
    $help['config_libraries_help'] = [
      'group' => 'configuration',
      'title' => $this->t('Configuration: Libraries: Help'),
      'content' => '<p>' . $this->t('The Webform module utilizes third-party Open Source libraries to enhance webform elements and to provide additional functionality.') . ' ' .
        $this->t('It is recommended that these libraries are installed in your Drupal installations /libraries directory.') . ' ' .
        $this->t('If these libraries are not installed, they will be automatically loaded from a CDN.') . ' ' .
        $this->t('All libraries are optional and can be excluded via the admin settings form.') .
        '</p>' .
        '<p>' . $this->t('There are three ways to download the needed third-party libraries.') . '</p>' .
        '<ul>' .
        '<li>' . $this->t('Generate a *.make.yml or composer.json file using <code>drush webform-libraries-make</code> or <code>drush webform-libraries-composer</code>.') . '</li>' .
        '<li>' . $this->t('Execute <code>drush webform-libraries-download</code>, which will download third-party libraries required by the Webform module.') . '</li>' .
        '<li>' . $this->t("Execute <code>drush webform-composer-update</code>, which will update your Drupal installation's composer.json to include the Webform module's selected libraries as repositories.") . '</li>' .
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
      'video_id' => 'admin',
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
      'video_id' => 'admin',
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
      'routes' => [
        // @see /admin/structure/webform/plugins/elements
        'webform.element_plugins',
      ],
    ];

    // Plugins: Handlers.
    $help['plugins_handlers'] = [
      'group' => 'plugins',
      'title' => $this->t('Plugins: Emails/Handlers'),
      'content' => $this->t('The <strong>Handler plugins</strong> overview page lists all available webform handler plugins.') . ' ' .
        $this->t('<strong>Handlers</strong> are used to route submitted data to external applications and send notifications & confirmations.'),
      'routes' => [
        // @see /admin/structure/webform/plugins/handlers
        'webform.handler_plugins',
      ],
    ];

    // Plugins: Exporters.
    $help['plugins_exporters'] = [
      'group' => 'plugins',
      'title' => $this->t('Plugins: Exporters'),
      'content' => $this->t('The <strong>Exporter plugins</strong> overview page lists all available results exporter plugins.') . ' ' .
        $this->t('<strong>Exporters</strong> are used to export results into a downloadable format that can be used by MS Excel, Google Sheets and other spreadsheet applications.'),
      'routes' => [
        // @see /admin/structure/webform/plugins/exporters
        'webform.exporter_plugins',
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
      'video_id' => 'source',
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
        $this->t('Multiple test submissions can be created using the devel_generate module.'),
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
      'video_id' => 'translate',
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/translate
        'entity.webform.config_translation_overview',
      ],
    ];

    /**************************************************************************/
    // Elements.
    /**************************************************************************/

    // Elements
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
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/settings/assets
        'entity.webform.settings_assets',
      ],
    ];

    // Settings: Access.
    $help['settings_access'] = [
      'group' => 'settings',
      'title' => $this->t('Settings: Access'),
      'content' => $this->t('The <strong>Access</strong> settings page allows an administrator to determine who can create, update, delete and purge webform submissions.'),
      'video_id' => 'access',
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

    // Submissions: Purge.
    $help['submissions_purge'] = [
      'group' => 'submissions',
      'title' => $this->t('Submissions: Purge'),
      'content' => $this->t('The <strong>Submissions purge</strong> page allows all submissions across all webforms to be deleted. <strong>PLEASE NOTE: THIS ACTION CANNOT BE UNDONE.</strong>'),
      'message_type' => 'warning',
      'routes' => [
        // @see /admin/structure/webform/results/purge
        'entity.webform_submission.collection_purge',
      ],
    ];

    // Submissions: Log.
    $help['submissions_log'] = [
      'group' => 'submissions',
      'title' => $this->t('Submissions: Log'),
      'content' => $this->t('The <strong>Submissions log</strong> page tracks all submission events for all webforms that have submission logging enabled. Submission logging can be enabled globally or on a per webform basis.'),
      'routes' => [
        // @see /admin/structure/webform/results/log
        'entity.webform_submission.collection_log',
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

    // Results: Log.
    $help['results_log'] = [
      'group' => 'submissions',
      'title' => $this->t('Results: Log'),
      'content' => $this->t('The <strong>Results Log</strong> lists all webform submission events for the current webform.'),
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/results/log
        'entity.webform.results_log',
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

    // Results: Clear.
    $help['results_clear'] = [
      'group' => 'submissions',
      'title' => $this->t('Results: Clear'),
      'content' => $this->t("The <strong>Clear</strong> page allows all submissions to a webform to be deleted."),
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/results/clear
        'entity.webform.results_clear',
      ],
    ];

    /**************************************************************************/
    // Submission.
    /**************************************************************************/

    $help['submission'] = [
      'group' => 'submission',
      'title' => $this->t('Submission: View'),
      'content' => $this->t("The <strong>View</strong> page displays a submission's general information and data."),
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
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/submission/{webform_submisssion}/yaml
        'entity.webform_submission.yaml',
        // @see /node/{node}/webform/submission/{webform_submisssion}/yaml
        'entity.node.webform_submission.yaml',
      ],
    ];

    $help['submission_log'] = [
      'group' => 'submission',
      'title' => $this->t('Submission: Log'),
      'content' => $this->t("The <strong>Log</strong> page shows all events and transactions for a submission."),
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/submission/{webform_submisssion}/log
        'entity.webform_submission.log',
        // @see /node/{node}/webform/submission/{webform_submission}/log
        'entity.node.webform_submission.log',
      ],
    ];

    $help['submission_edit'] = [
      'group' => 'submission',
      'title' => $this->t('Submission: Edit'),
      'content' => $this->t("The <strong>Edit</strong> form allows the administrator to update a submission."),
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
    // Devel.
    /**************************************************************************/

    // Devel: Export.
    $help['devel_export'] = [
      'group' => 'devel',
      'title' => $this->t('Devel: Export'),
      'content' => $this->t("The <strong>Export</strong> form allows developers to quickly export a single webform's YAML configuration file.") . ' ' .
        $this->t('If you run into any issues with a webform, you can also attach the below configuration (without any personal information) to a new ticket in the Webform module\'s <a href=":href">issue queue</a>.', [':href' => 'https://www.drupal.org/project/issues/webform']),
      'video_id' => 'help',
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/export
        'entity.webform.export_form',
      ],
    ];

    // Devel: Schema.
    $help['devel_schema'] = [
      'group' => 'devel',
      'title' => $this->t('Devel: Webform Schema'),
      'content' => $this->t("The <strong>Schema</strong> page displays an overview of a webform's elements and specified data types, which can be used to map webform submissions to an external API."),
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/schema
        'entity.webform.schema_form',
      ],
    ];

    /**************************************************************************/
    // Modules.
    /**************************************************************************/

    // Webform Node.
    $help['webform_node'] = [
      'group' => 'webform_nodes',
      'title' => $this->t('Webform Node'),
      'content' => $this->t("A <strong>Webform Node</strong> allows webforms to be fully integrated into a website as nodes."),
      'video_id' => 'blocks',
      'paths' => [
        '/node/add/webform',
      ],
    ];
    $help['webform_node_reference'] = [
      'group' => 'webform_nodes',
      'title' => $this->t('Webform Node: References'),
      'content' => $this->t("The <strong>Reference</strong> pages displays an overview of a webform's references and allows you to quickly create new references (a.k.a Webform nodes)."),
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
      'routes' => [
        // @see /node/{node}/webform/results/submissions
        'entity.node.webform.results_submissions',
      ],
    ];
    $help['webform_node_results_log'] = [
      'group' => 'webform_nodes',
      'title' => $this->t('Webform Node: Results: Log'),
      'content' => $this->t('The <strong>Results Log</strong> lists all webform submission events for the current webform.'),
      'routes' => [
        // @see /node/{node}/webform/results/log
        'entity.node.webform.results_log',
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
    $help['webform_node_results_clear'] = [
      'group' => 'webform_nodes',
      'title' => $this->t('Webform Node: Results: Clear'),
      'content' => $this->t("The <strong>Clear</strong> page allows all submissions to a webform node to be deleted."),
      'routes' => [
        // @see /node/{node}/webform/results/clear
        'entity.node.webform.results_clear',
      ],
    ];

    // Webform Block.
    $help['webform_block'] = [
      'group' => 'webform_blocks',
      'title' => $this->t('Webform Block'),
      'content' => $this->t("A <strong>Webform Block</strong> allows a webform to be placed anywhere on a website."),
      'video_id' => 'blocks',
      'paths' => [
        '/admin/structure/block/add/webform_block/*',
      ],
    ];

    /**************************************************************************/
    // Messages.
    /**************************************************************************/

    // Release.
    $t_args = [
      '@version' => $this->getVersion(),
      ':href' => 'https://www.drupal.org/project/webform/releases/' . $this->getVersion(),
    ];
    $help['message_release'] = [
      'group' => 'messages',
      'title' => $this->t('Message: Release'),
      'content' => $this->t('You have successfully updated to the @version release of the Webform module. <a href=":href">Learn more</a>', $t_args),
      'message_type' => 'status',
      'message_close' => TRUE,
      'message_storage' => WebformMessage::STORAGE_STATE,
      'access' => $this->currentUser->hasPermission('administer webform'),
      'reset_version' => TRUE,
      'routes' => [
        // @see /admin/modules
        'system.modules_list',
        // @see /admin/reports/updates
        'update.status',
      ],
    ];

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

    /**************************************************************************/
    // Promotions.
    // Disable promotions via Webform admin settings.
    // (/admin/structure/webform/config/advanced).
    /**************************************************************************/

    // Promotions: Lingotek.
    $help['promotion_lingotek'] = [
      'group' => 'promotions',
      'title' => $this->t('Promotions: Lingotek'),
      'content' => $this->t("Help <strong>support</strong> the Webform module and internationalize your website using the Lingotek-Inside Drupal Module for continuous translation. <em>Multilingual capability + global access = increased web traffic.</em>"),
      'video_id' => 'promotion_lingotek',
      'message_type' => 'promotion_lingotek',
      'message_close' => TRUE,
      'message_storage' => WebformMessage::STORAGE_STATE,
      'attached' => ['library' => ['webform/webform.promotions']],
      'access' => $this->currentUser->hasPermission('administer webform')
        && !$this->configFactory->get('webform.settings')->get('ui.promotions_disabled'),
      'reset_version' => TRUE,
      'routes' => [
        // /admin/structure/webform/config/translate
        'config_translation.item.overview.webform.config',
        // @see /admin/structure/webform/manage/{webform}/translate
        'entity.webform.config_translation_overview',
      ],
    ];

    /**************************************************************************/

    // Initialize help.
    foreach ($help as $id => &$help_info) {
      $help_info += [
        'id' => $id,
        'uses' => TRUE,
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
