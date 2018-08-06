<?php

namespace Drupal\webform;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Url;
use Drupal\webform\Element\WebformMessage;
use Drupal\webform\Utility\WebformArrayHelper;

/**
 * Webform help manager.
 */
class WebformHelpManager implements WebformHelpManagerInterface {

  use StringTranslationTrait;

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
   * The Webform addo-ns manager.
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
   * @var \Drupal\webform\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * Constructs a WebformHelpManager object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher.
   * @param \Drupal\webform\WebformAddOnsManagerInterface $addons_manager
   *   The Webform add-ons manager.
   * @param \Drupal\webform\WebformLibrariesManagerInterface $libraries_manager
   *   The Webform libraries manager.
   * @param \Drupal\webform\WebformElementManagerInterface $element_manager
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

    $this->help = $this->initHelp();
    $this->videos = $this->initVideos();
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
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildIndex() {
    $build = [
      '#prefix' => '<div class="webform-help-accordion">',
      '#suffix' => '</div>',
    ];
    $build['about'] = $this->buildAbout();
    if ($this->configFactory->get('webform.settings')->get('ui.video_display') !== 'hidden') {
      $build['videos'] = $this->buildVideos();
    }
    $build['uses'] = $this->buildUses();
    $build['elements'] = $this->buildElements();
    $build['addons'] = $this->buildAddOns();
    $build['libraries'] = $this->buildLibraries();
    $build['#attached']['library'][] = 'webform/webform.help';
    return $build;
  }

  /****************************************************************************/
  // Index sections.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function buildAbout() {
    return [
      'title' => [
        '#markup' => $this->t('About the Webform module'),
        '#prefix' => '<h3 id="about">',
        '#suffix' => '</h3>',
      ],
      'content' => [
        '#markup' => '<p>' . $this->t('The Webform module is a webform builder and submission manager for Drupal 8.') . '</p>',
        '#prefix' => '<div>',
        '#suffix' => '</div>',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildElements($docs = FALSE) {
    $build = [
      'title' => [
        '#markup' => $this->t('Form elements'),
        '#prefix' => '<h3 id="elements">',
        '#suffix' => '</h3>',
      ],
      'content' => [
        '#markup' => '<p>' . $this->t('Below is a list of all available form and render elements.') . '</p>',
        '#prefix' => '<div>',
        '#suffix' => '</div>',
      ],
    ];

    $definitions = $this->elementManager->getDefinitions();
    $definitions = $this->elementManager->getSortedDefinitions($definitions, 'category');
    $grouped_definitions = $this->elementManager->getGroupedDefinitions($definitions);
    unset($grouped_definitions['Other elements']);
    foreach ($grouped_definitions as $category_name => $elements) {
      $build['content'][$category_name]['title'] = [
        '#markup' => $category_name,
        '#prefix' => '<h2>',
        '#suffix' => '</h2>',
      ];
      $build['content'][$category_name]['elements'] = [
        '#prefix' => '<dl>',
        '#suffix' => '</dl>',
      ];
      foreach ($elements as $element_name => $element) {
        /** @var \Drupal\webform\WebformElementInterface $webform_element */
        $webform_element = $this->elementManager->createInstance($element_name);

        if ($webform_element->isHidden()) {
          continue;
        }

        if ($api_url = $webform_element->getPluginApiUrl()) {
          $build['content'][$category_name]['elements'][$element_name]['title'] = [
            '#type' => 'link',
            '#title' => $element['label'],
            '#url' => $api_url,
          ];
        }
        else {
          $build['content'][$category_name]['elements'][$element_name]['title'] = [
            '#markup' => $element['label'],
          ];
        }
        $build['content'][$category_name]['elements'][$element_name]['title'] += [
          '#prefix' => '<dt>',
          '#suffix' => '</dt>',
        ];

        $build['content'][$category_name]['elements'][$element_name]['description'] = [
          '#markup' => $element['description'],
          '#prefix' => '<dd>',
          '#suffix' => '</dd>',
        ];
      }
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildUses($docs = FALSE) {
    $build = [
      'title' => [
        '#markup' => $this->t('Uses'),
        '#prefix' => '<h3 id="uses">',
        '#suffix' => '</h3>',
      ],
      'content' => [
        '#prefix' => '<div>',
        '#suffix' => '</div>',
        'help' => [
          '#prefix' => '<dl>',
          '#suffix' => '</dl>',
        ],
      ],
    ];
    foreach ($this->help as $id => $help_info) {
      // Check that help item should be displated under 'Uses'.
      if (empty($help_info['uses'])) {
        continue;
      }

      // Title.
      $build['content']['help'][$id]['title'] = [
        '#prefix' => '<dt>',
        '#suffix' => '</dt>',
      ];
      if (isset($help_info['url'])) {
        $build['content']['help'][$id]['title']['link'] = [
          '#type' => 'link',
          '#url' => $help_info['url'],
          '#title' => $help_info['title'],
        ];
      }
      else {
        $build['content']['help'][$id]['title']['#markup'] = $help_info['title'];
      }
      // Content.
      $build['content']['help'][$id]['content'] = [
        '#prefix' => '<dd>',
        '#suffix' => '</dd>',
        'content' => [
          '#theme' => 'webform_help',
          '#info' => $help_info,
          '#docs' => TRUE,
        ],
      ];
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildVideos($docs = FALSE) {
    $build = [
      'title' => [
        '#markup' => $this->t('Watch videos'),
        '#prefix' => '<h3 id="videos">',
        '#suffix' => '</h3>',
      ],
      'content' => [
        '#prefix' => '<div>',
        '#suffix' => '</div>',
        'help' => [
          '#prefix' => '<dl>',
          '#suffix' => '</dl>',
        ],
      ],
    ];
    if ($docs) {
      foreach ($this->videos as $id => $video) {
        // Title.
        $build['content']['help'][$id]['title'] = [
          '#type' => 'link',
          '#title' => $video['title'],
          '#url' => Url::fromUri('https://www.youtube.com/watch', ['query' => ['v' => $video['youtube_id']]]),
          '#prefix' => '<dt>',
          '#suffix' => '</dt>',
        ];
        // Content.
        $build['content']['help'][$id]['content'] = [
          '#prefix' => '<dd>',
          '#suffix' => '</dd>',
          '#markup' => $video['content'],
        ];
      }
    }
    else {
      foreach ($this->videos as $id => $video) {
        // Title.
        $build['content']['help'][$id]['title'] = [
          '#markup' => $video['title'],
          '#prefix' => '<dt>',
          '#suffix' => '</dt>',
        ];
        // Content.
        $build['content']['help'][$id]['content'] = [
          '#prefix' => '<dd>',
          '#suffix' => '</dd>',
          'content' => [
            '#theme' => 'webform_help',
            '#info' => $video,
          ],
        ];
      }
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildAddOns($docs = FALSE) {
    // Libraries.
    $build = [
      'title' => [
        '#markup' => $this->t('Add-ons'),
        '#prefix' => '<h3 id="addons">',
        '#suffix' => '</h3>',
      ],
      'content' => [
        '#markup' => '<p>' . $this->t("Below is a list of modules and projects that extend and/or provide additional functionality to the Webform module and Drupal's Form API.") . '</p>',
        '#prefix' => '<div>',
        '#suffix' => '</div>',
      ],
    ];

    $categories = $this->addOnsManager->getCategories();
    foreach ($categories as $category_name => $category) {
      $build['content'][$category_name]['title'] = [
        '#markup' => $category['title'],
        '#prefix' => '<h2>',
        '#suffix' => '</h2>',
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
            '#suffix' => '</dt>',
          ],
          'description' => [
            '#markup' => $project['description'] . ((isset($project['notes'])) ? '<br/><em>(' . $project['notes'] . ')</em>' : ''),
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
    // Libraries.
    $build = [
      'title' => [
        '#markup' => $this->t('External Libraries'),
        '#prefix' => '<h3 id="libraries">',
        '#suffix' => '</h3>',
      ],
      'content' => [
        '#prefix' => '<div>',
        '#suffix' => '</div>',
        'description' => [
          '#markup' => '<p>' . $this->t('The Webform module utilizes the third-party Open Source libraries listed below to enhance webform elements and to provide additional functionality.') . ' ' .
            $this->t('It is recommended that these libraries be installed in your Drupal installations /libraries directory.') . ' ' .
            $this->t('If these libraries are not installed, they are automatically loaded from a CDN.') . ' ' .
            $this->t('All libraries are optional and can be excluded via the admin settings form.') .
            '</p>' .
            '<p>' . $this->t('There are twos ways to download the needed third party libraries.') . '</p>' .
            '<ul>' .
              '<li>' . $this->t('Generate a *.make.yml or composer.json file using <code>drush webform-libraries-make</code> or <code>drush webform-libraries-composer</code>.') . '</li>' .
              '<li>' . $this->t('Execute <code>drush webform-libraries-download</code> which will download all included libraries.') . '</li>' .
            '</ul>' .
            '<hr/>',
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
            '#suffix' => '<br/>'
          ],
          'notes' => [
            '#markup' => $library['notes'] .
              ($elements ? ' <strong>' . $this->t('Required by @type @elements.', ['@type' => WebformArrayHelper::toString($elements), '@elements' => $this->formatPlural(count($elements), $this->t('element'), $this->t('elements'))]) . '</strong>': ''),
            '#prefix' => '<em>(',
            '#suffix' => ')</em><br/>'
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
    // Remove empty rows
    $html = preg_replace('#<tr>(<td></td>)+?</tr>#', '', $html);
    // Remove empty links
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

    // Add cell colors
    $html = preg_replace('#<tr><td>([^<]+)</td>#', '<tr><td bgcolor="' . $feature_color . '">\1</td>', $html);
    $html = preg_replace('#<td>Yes([^<]*)</td>#', '<td bgcolor="' . $yes_color . '"><img src="https://www.drupal.org/misc/watchdog-ok.png" alt="Yes"> \1</td>', $html);
    $html = preg_replace('#<td>No([^<]*)</td>#', '<td bgcolor="' . $custom_color . '"><img src="https://www.drupal.org/misc/watchdog-error.png" alt="No"> \1</td>', $html);
    $html = preg_replace('#<td>([^<]*)</td>#', '<td bgcolor="' . $no_color . '"><img src="https://www.drupal.org/misc/watchdog-warning.png" alt="Warning"> \1</td>', $html);

    // Convert URLs to links with titles.
    $links = [
      'https://www.drupal.org/docs/8/modules/webform' => $this->t('Webform Documentation'),
      'https://www.drupal.org/docs/8/core/modules/contact/overview' => $this->t('Contact Documentation'),
      'https://www.drupal.org/docs/8/modules/webform/webform-videos' => $this->t('Webform Videos'),
      'https://www.drupal.org/docs/8/modules/webform/webform-cookbook' => $this->t('Webform Cookbook'),
      'https://www.drupal.org/project/project_module?text=signature' => $this->t('Signature related-projects'),
    ];
    foreach ($links as $link_url => $link_title) {
      $html = preg_replace('#([^"/])' . preg_quote($link_url, '#') . '([^"/])#', '\1<a href="' . $link_url . '">' . $link_title . '</a>\2', $html);
    }

    // Create fake filter object with settings.
    $filter = (object) ['settings' => ['filter_url_length' => 255]];
    $html = _filter_url($html, $filter);

    // Link *,module.
    $html = preg_replace('/([a-z0-9_]+)\.module/', '<a href="https://www.drupal.org/project/\1">\1.module</a>', $html);

    // Tidy
    if (class_exists('\tidy')) {
      $tidy = new \tidy();
      $tidy->parseString($html, ['show-body-only' => TRUE, 'wrap' => '0'], 'utf8');
      $tidy->cleanRepair();
      $html = tidy_get_output($tidy);
    }

    return [
      'title' => [
        '#markup' => $this->t('Form builder comparison'),
        '#prefix' => '<h3 id="comparison">',
        '#suffix' => '</h3>',
      ],
      'content' => [
        '#prefix' => '<div>',
        '#suffix' => '</div>',
        'description' => [
          '#markup' => '<p>' . $this->t("Here is a detailed feature-comparison of Webform 8.x-5.x and Contact Storage 8.x-1.x.&nbsp;It's worth noting that Contact Storage relies on the Contact module which in turn relies on the Field UI; Contact Storage out of the box is a minimalistic solution with limited (but useful!) functionality. This means it can be extended with core mechanisms such as CRUD entity hooks and overriding services; also there's a greater chance that a general purpose module will play nicely with it (eg. the Conditional Fields module is for entity form displays in general, not the Contact module).") . '</p>' .
            '<p>' . $this->t("Webform is much heavier; it has a great deal of functionality enabled right within the one module, and that's on top of supplying all the normal field elements (because it doesn't just use the Field API)") . '</p>',
        ],
        'table' => ['#markup' => $html],
        'google' => [
          '#markup' => '<p>' . $this->t('Please post comments and feedback to this <a href=":href">Google Sheet</a>.', [':href' => 'https://docs.google.com/spreadsheets/d/1zNt3WsKxDq2ZmMHeYAorNUUIx5_yiDtDVUIKXtXaq4s/edit?usp=sharing']) . '</p>',
        ],
      ],
    ];
  }

  /****************************************************************************/
  // Videos.
  /****************************************************************************/

  /**
   * {@inheritdoc}
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
      'title' => $this->t('Installing the Webform module and third party libraries'),
      'content' => $this->t('This screencast walks through installing the core Webform module, sub-module, required libraries, and add-ons.'),
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

    $videos['form_settings'] = [
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
      'title' => $this->t('Getting Help'),
      'content' => $this->t('This screencast walks through getting help with the Webform module.'),
      'youtube_id' => 'sRXUR2c2brA',
    ];

    foreach ($videos as $id => &$video_info) {
      $video_info['id'] = $id;
    }

    return $videos;
  }

  /**
   * Initialize help.
   *
   * @return array
   *   An associative array containing help.
   */
  protected function initHelp() {
    $help = [];

    // Install.
    $t_args = [
      ':addons_href' => Url::fromRoute('webform.addons')->toString(),
      ':submodules_href' => Url::fromRoute('system.modules_list', [], ['fragment' => 'edit-modules-webform'])->toString(),
      ':libraries_href' => Url::fromRoute('help.page', ['name' => 'webform'], ['fragment' => 'libraries'])->toString(),
    ];
    $help['install'] = [
      'routes' => [
        // @see /admin/modules
        'system.modules_list',
      ],
      'title' => $this->t('Installing the Webform module'),
      'content' => $this->t('<strong>Congratulations!</strong> You have successfully installed the Webform module. Please make sure to install additional <a href=":libraries_href">third-party libraries</a>, <a href=":submodules_href">sub-modules</a>, and optional <a href=":addons_href">add-ons</a>.', $t_args),
      'message_type' => 'info',
      'message_close' => TRUE,
      'message_storage' => WebformMessage::STORAGE_STATE,
      'access' => $this->currentUser->hasPermission('administer webform'),
      'video_id' => 'install',
      'uses' => FALSE,
    ];

    // Release.
    $module_info = Yaml::decode(file_get_contents($this->moduleHandler->getModule('webform')->getPathname()));
    $version = (isset($module_info['version']) && !preg_match('/^8.x-5.\d+-.*-dev$/', $module_info['version'])) ? $module_info['version'] : '8.x-5.x-dev';
    $installed_version = $this->state->get('webform.version');
    // Reset storage state if the version has changed.
    if ($installed_version != $version) {
      WebformMessage::resetClosed(WebformMessage::STORAGE_STATE, 'webform.help.release');
      $this->state->set('webform.version', $version);
    }
    $t_args = [
      '@version' => $version,
      ':href' => 'https://www.drupal.org/project/webform/releases/' . $version,
    ];
    $help['release'] = [
      'routes' => [
        // @see /admin/structure/webform
        'entity.webform.collection',
      ],
      'title' => $this->t('You have successfully updated...'),
      'content' => $this->t('You have successfully updated to the @version release of the Webform module. <a href=":href">Learn more</a>', $t_args),
      'message_type' => 'status',
      'message_close' => TRUE,
      'message_storage' => WebformMessage::STORAGE_STATE,
      'access' => $this->currentUser->hasPermission('administer webform'),
      'uses' => FALSE,
    ];

    // Introduction.
    $help['introduction'] = [
      'routes' => [
        // @see /admin/structure/webform
        'entity.webform.collection',
      ],
      'title' => $this->t('Welcome'),
      'content' => $this->t('Welcome to the Webform module for Drupal 8.'),
      'message_type' => 'info',
      'message_close' => TRUE,
      'message_storage' => WebformMessage::STORAGE_USER,
      'access' => $this->currentUser->hasPermission('administer webform'),
      'video_id' => 'introduction',
    ];

    /****************************************************************************/
    // General.
    /****************************************************************************/

    // Webforms.
    $help['webforms'] = [
      'routes' => [
        // @see /admin/structure/webform
        'entity.webform.collection',
      ],
      'title' => $this->t('Managing webforms'),
      'url' => Url::fromRoute('entity.webform.collection'),
      'content' => $this->t('The Forms page lists all available webforms, which can be filtered by title, description, and/or elements.'),
      'video_id' => 'forms',
    ];

    // Templates.
    if ($this->moduleHandler->moduleExists('webform_templates')) {
      $help['templates'] = [
        'routes' => [
          // @see /admin/structure/webform/templates
          'entity.webform.templates',
        ],
        'title' => $this->t('Using templates'),
        'url' => Url::fromRoute('entity.webform.templates'),
        'content' => $this->t('The Templates page lists reusable templates that can be duplicated and customized to create new webforms.'),
        'video_id' => 'forms',
      ];
    }

    // Results.
    $help['results'] = [
      'routes' => [
        // @see /admin/structure/webform/results/manage
        'entity.webform_submission.collection',
      ],
      'title' => $this->t('Managing results'),
      'url' => Url::fromRoute('entity.webform_submission.collection'),
      'content' => $this->t('The Results page lists all incoming submissions for all webforms.'),
    ];

    // Results.
    $help['results'] = [
      'routes' => [
        // @see /admin/structure/webform/results/log
        'entity.webform_submission.results_log',
      ],
      'title' => $this->t('Log'),
      'url' => Url::fromRoute('entity.webform_submission.results_log'),
      'content' => $this->t('The Log page lists all submission events for all webforms.'),
    ];

    // Settings.
    $help['settings'] = [
      'routes' => [
        // @see /admin/structure/webform/settings
        'webform.settings',
      ],
      'title' => $this->t('Defining default settings'),
      'url' => Url::fromRoute('webform.settings'),
      'content' => $this->t('The Settings page allows administrators to manage global webform and UI configuration settings, including updating default labels & descriptions, settings default format, and defining test dataset.'),
      'video_id' => 'admin',
    ];

    // Options.
    $help['options'] = [
      'routes' => [
        // @see /admin/structure/webform/settings/options/manage
        'entity.webform_options.collection',
      ],
      'title' => $this->t('Defining options'),
      'url' => Url::fromRoute('entity.webform_options.collection'),
      'content' => $this->t('The Options page lists predefined options which are used to build select menus, radio buttons, checkboxes and likerts.') . ' ' .
      $this->t('To find and download additional options, go to <a href=":href">Webform 8.x-5.x: Options</a>.', [':href' => 'https://www.drupal.org/node/2845530']),
    ];

    // Elements.
    $help['elements'] = [
      'routes' => [
        // @see /admin/structure/webform/settings/elements
        'webform.element_plugins',
      ],
      'title' => $this->t('Webform element plugins'),
      'url' => Url::fromRoute('webform.element_plugins'),
      'content' => $this->t('The Elements page lists all available webform element plugins.') . ' ' .
      $this->t('Webform element plugins are used to enhance existing render/form elements. Webform element plugins provide default properties, data normalization, custom validation, element configuration webform, and customizable display formats.'),
    ];

    // Handlers.
    $help['handlers'] = [
      'routes' => [
        // @see /admin/structure/webform/settings/handlers
        'webform.handler_plugins',
      ],
      'title' => $this->t('Webform handler plugins'),
      'url' => Url::fromRoute('webform.handler_plugins'),
      'content' => $this->t('The Handlers page lists all available webform handler plugins.') . ' ' .
      $this->t('Handlers are used to route submitted data to external applications and send notifications & confirmations.'),
    ];

    // Exporters.
    $help['exporters'] = [
      'routes' => [
        // @see /admin/structure/webform/settings/exporters
        'webform.exporter_plugins',
      ],
      'title' => $this->t('Results exporter plugins'),
      'url' => Url::fromRoute('webform.exporter_plugins'),
      'content' => $this->t('The Exporters page lists all available results exporter plugins.') . ' ' .
      $this->t('Exporters are used to export results into a downloadable format that can be used by MS Excel, Google Sheets, and other spreadsheet applications.'),
    ];

    // Third party settings.
    $help['third_party'] = [
      'routes' => [
        // @see /admin/structure/webform/settings/third-party
        'webform.admin_settings.third_party',
      ],
      'title' => $this->t('Configuring global third party settings'),
      'url' => Url::fromRoute('webform.admin_settings.third_party'),
      'content' => $this->t('The Third party settings page allows contrib and custom modules to define global settings that are applied to all webforms and submissions.'),
    ];

    // Addons.
    $help['addons'] = [
      'routes' => [
        // @see /admin/structure/webform/addons
        'webform.addons',
      ],
      'title' => $this->t('Extend the Webform module'),
      'url' => Url::fromRoute('webform.addons'),
      'content' => $this->t('The Add-ons page includes a list of modules and projects that extend and/or provide additional functionality to the Webform module and Drupal\'s Form API.  If you would like a module or project to be included in the below list, please submit a request to the <a href=":href">Webform module\'s issue queue</a>.', [':href' => 'https://www.drupal.org/node/add/project-issue/webform']),
    ];

    /****************************************************************************/
    // Webform.
    /****************************************************************************/

    // Webform elements.
    if (!$this->moduleHandler->moduleExists('webform_ui')) {
      $help['webform_elements_warning'] = [
        'routes' => [
          // @see /admin/structure/webform/manage/{webform}
          'entity.webform.edit_form',
        ],
        'title' => $this->t('Webform UI is disabled'),
        'content' => $this->t('Please enable Webform UI module if you would like to add elements from UI.'),
        'message_type' => 'warning',
        'message_close' => TRUE,
        'message_storage' => WebformMessage::STORAGE_STATE,
        'access' => $this->currentUser->hasPermission('administer webform') && $this->currentUser->hasPermission('administer modules'),
        'uses' => FALSE,
      ];
    }

    $help['webform_elements'] = [
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}
        'entity.webform.edit_form',
      ],
      'title' => $this->t('Building a webform'),
      'content' => $this->t('The Webform elements page allows users to add, update, duplicate, and delete webform elements and wizard pages.'),
      'video_id' => 'form_elements',
    ];

    // Webform source.
    $help['webform_source'] = [
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/source
        'entity.webform.source_form',
      ],
      'title' => $this->t('Editing YAML source'),
      'content' => $this->t("The (View) Source page allows developers to edit a webform's render array using YAML markup.") . ' ' .
      $this->t("Developers can use the (View) Source page to quickly alter a webform's labels, cut-n-paste multiple elements, reorder elements, and add customize properties and markup to elements."),
      'video_id' => 'source',
    ];

    // Webform test.
    $help['webform_test'] = [
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/test
        'entity.webform.test',
        // @see /node/{node}/webform/test
        'entity.node.webform.test',
      ],
      'title' => $this->t('Testing a webform'),
      'content' => $this->t("The Webform test page allows a webform to be tested using a customizable test dataset.") . ' ' .
      $this->t('Multiple test submissions can be created using the devel_generate module.'),
    ];

    // Webform settings.
    $help['webform_settings'] = [
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/settings
        'entity.webform.settings_form',
      ],
      'title' => $this->t('Customizing webform settings'),
      'content' => $this->t("The Webform settings page allows a webform's labels, messaging, and behaviors to be customized.") . ' ' .
      $this->t('Administrators can open/close a webform, enable/disable drafts, allow previews, set submission limits, and disable the saving of results.'),
      'video_id' => 'form_settings',
    ];

    // Webform assets.
    $help['webform_assets'] = [
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/assets
        'entity.webform.assets_form',
      ],
      'title' => $this->t('Adding custom CSS/JS to a webform.'),
      'content' => $this->t("The Webform assets page allows site builders to attach custom CSS and JavaScript to a webform."),
    ];

    // Webform access controls.
    $help['webform_access'] = [
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/access
        'entity.webform.access_form',
      ],
      'title' => $this->t('Controlling access to submissions'),
      'content' => $this->t('The Webform access control page allows administrator to determine who can create, update, delete, and purge webform submissions.'),
      'video_id' => 'access',
    ];

    // Webform handlers.
    $help['webform_handlers'] = [
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/handlers
        'entity.webform.handlers_form',
      ],
      'title' => $this->t('Enabling webform handlers'),
      'content' => $this->t('The Webform handlers page lists additional handlers (aka behaviors) that can process webform submissions.') . ' ' .
      $this->t('Handlers are <a href=":href">plugins</a> that act on a webform submission.', [':href' => 'https://www.drupal.org/developing/api/8/plugins']) . ' ' .
      $this->t('For example, sending email confirmations and notifications is done using the Email handler which is provided by the Webform module.'),
      'video_id' => 'submissions',
    ];

    // Webform third party settings.
    $help['webform_third_party'] = [
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/third_party
        'entity.webform.third_party_settings_form',
      ],
      'title' => $this->t('Configuring third party settings'),
      'content' => $this->t('The Third party settings page allows contrib and custom modules to define webform specific customization settings.'),
    ];

    // Webform translations.
    $help['webform_translations'] = [
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/translate
        'entity.webform.config_translation_overview',
      ],
      'title' => $this->t('Translating a webform'),
      'content' => $this->t("The Translation page allows a webform's configuration and elements to be translated into multiple languages."),
      'video_id' => 'translate',
    ];

    /****************************************************************************/
    // Results.
    /****************************************************************************/

    // Webform results.
    $help['webform_results'] = [
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/results/submissions
        'entity.webform.results_submissions',
        // @see /node/{node}/webform/results/submissions
        'entity.node.webform.results_submissions',
      ],
      'title' => $this->t('Managing results'),
      'content' => $this->t("The Results page displays an overview of a webform's submissions. This page can be used to generate a customized report.") . ' ' .
      $this->t("Submissions can be reviewed, updated, flagged, annotated, and downloaded."),
      'video_id' => 'submissions',
    ];

    // Webform log.
    $help['webform_log'] = [
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/results/log
        'entity.webform.results_log',
        // @see /node/{node}/webform/results/log
        'entity.node.webform.results_log',
      ],
      'title' => $this->t('Results log'),
      'content' => $this->t('The Results log lists all logged webform submission events for the current webform.'),
    ];

    // Webform download.
    $help['webform_download'] = [
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/results/download
        'entity.webform.results_export',
        // @see /node/{node}/webform/results/download
        'entity.node.webform.results_export',
      ],
      'title' => $this->t('Downloading results'),
      'content' => $this->t("The Download page allows a webform's submissions to be exported in to a customizable CSV (Comma Separated Values) file."),
    ];

    if ($this->moduleHandler->moduleExists('webform_devel')) {
      // Webform Export.
      $help['webform_export'] = [
        'routes' => [
          // @see /admin/structure/webform/manage/{webform}/export
          'entity.webform.export_form',
        ],
        'title' => $this->t('Exporting configuration'),
        'content' => $this->t("The Export (form) page allows developers to quickly export a single webform's configuration file.") . ' ' .
        $this->t('If you run into any issues with a webform, you can also attach the below configuration (without any personal information) to a new ticket in the Webform module\'s <a href=":href">issue queue</a>.', [':href' => 'https://www.drupal.org/project/issues/webform']),
        'video_id' => 'help',
      ];
    }

    /****************************************************************************/
    // Submission
    /****************************************************************************/

    // Log.
    $help['submission_log'] = [
      'routes' => [
        // @see /admin/structure/webform/manage/{webform}/submission/{webform_submission}/log
        'entity.webform_submission.log',
        // @see /node/{node}/webform/submission/{webform_submission}/log
        'entity.node.webform_submission.log',
      ],
      'title' => $this->t('Submission log'),
      'url' => Url::fromRoute('entity.webform_submission.results_log'),
      'content' => $this->t('The Submission log lists all events logged for this submission.'),
    ];

    /****************************************************************************/
    // Modules
    /****************************************************************************/

    // Webform Node.
    $help['webform_node'] = [
      'paths' => [
        '/node/add/webform',
      ],
      'title' => $this->t('Creating a webform node'),
      'content' => $this->t("A webform node allows webforms to be fully integrated into a website as nodes."),
      'video_id' => 'blocks',
    ];

    // Webform Block.
    $help['webform_block'] = [
      'paths' => [
        '/admin/structure/block/add/webform_block/*',
      ],
      'title' => $this->t('Creating a webform block'),
      'content' => $this->t("A webform block allows a webform to be placed anywhere on a website."),
      'video_id' => 'blocks',
    ];

    foreach ($help as $id => &$help_info) {
      $help_info += [
        'id' => $id,
        'uses' => TRUE,
      ];
    }

    return $help;
  }

}
