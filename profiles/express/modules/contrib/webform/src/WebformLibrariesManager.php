<?php

namespace Drupal\webform;

use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\webform\Utility\WebformArrayHelper;

/**
 * Webform libraries manager.
 */
class WebformLibrariesManager implements WebformLibrariesManagerInterface {

  use StringTranslationTrait;

  /**
   * The library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

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
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Libraries that provides additional functionality to the Webform module.
   *
   * @var array
   */
  protected $libraries;

  /**
   * Excluded libraries.
   *
   * @var array
   */
  protected $excludedLibraries;

  /**
   * Constructs a WebformLibrariesManager object.
   *
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   *   The library discovery service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(LibraryDiscoveryInterface $library_discovery, ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, RendererInterface $renderer) {
    $this->libraryDiscovery = $library_discovery;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function requirements($cli = FALSE) {
    $cdn = $this->configFactory->get('webform.settings')->get('requirements.cdn') ?: FALSE;

    $libraries = $this->getLibraries();

    // Track stats.
    $severity = REQUIREMENT_OK;
    $stats = [
      '@total' => count($libraries),
      '@installed' => 0,
      '@excluded' => 0,
      '@missing' => 0,
    ];

    // Build library info array.
    $info = [
      '#prefix' => '<p><hr/></p><dl>',
      '#suffix' => '</dl>',
    ];

    foreach ($libraries as $library_name => $library) {
      // Excluded.
      if ($this->isExcluded($library_name)) {
        $stats['@excluded']++;
        continue;
      }

      $library_path = '/libraries/' . $library_name;
      $library_exists = (file_exists(DRUPAL_ROOT . $library_path)) ? TRUE : FALSE;

      $t_args = [
        '@title' => $library['title'],
        '@version' => $library['version'],
        '@path' => $library_path,
        ':download_href' => $library['download_url']->toString(),
        ':homepage_href' => $library['homepage_url']->toString(),
        ':external_href' => 'https://www.drupal.org/docs/8/theming-drupal-8/adding-stylesheets-css-and-javascript-js-to-a-drupal-8-theme#external',
        ':install_href' => ($this->moduleHandler->moduleExists('help')) ? Url::fromRoute('help.page', ['name' => 'webform'], ['fragment' => 'libraries'])->toString() : 'https://www.drupal.org/docs/8/modules/webform/webform-libraries',
        ':settings_libraries_href' => Url::fromRoute('webform.config.libraries')->toString(),
        ':settings_elements_href' => Url::fromRoute('webform.config.elements')->toString(),
      ];

      if (!empty($library['module'])) {
        // Installed by module.
        $t_args['@module'] = $library['module'];
        $t_args[':module_href'] = 'https://www.drupal.org/project/' . $library['module'];
        $stats['@installed']++;
        $title = $this->t('<strong>@title</strong> (Installed)', $t_args);
        $description = $this->t('The <a href=":homepage_href">@title</a> library is installed by the <b><a href=":module_href">@module</a></b> module.', $t_args);
      }
      elseif ($library_exists) {
        // Installed.
        $stats['@installed']++;
        $title = $this->t('<strong>@title @version</strong> (Installed)', $t_args);
        $description = $this->t('The <a href=":homepage_href">@title</a> library is installed in <b>@path</b>.', $t_args);
      }
      elseif ($cdn) {
        // Missing.
        $stats['@missing']++;
        $title = $this->t('<span class="color-warning"><strong>@title @version</strong> (CDN).</span>', $t_args);
        $description = $this->t('Please download the <a href=":homepage_href">@title</a> library from <a href=":download_href">:download_href</a> and copy it to <b>@path</b> or use <a href=":install_href">Drush</a> to install this library.', $t_args);
        $severity = REQUIREMENT_WARNING;
      }
      else {
        // CDN.
        $stats['@missing']++;
        $title = $this->t('<strong>@title @version</strong> (CDN).', $t_args);
        $description = $this->t('The <a href=":homepage_href">@title</a> library is <a href=":external_href">externally hosted libraries</a> and loaded via a Content Delivery Network (CDN).', $t_args);
      }

      $info[$library_name] = [];
      $info[$library_name]['title'] = [
        '#markup' => $title,
        '#prefix' => '<dt>',
        '#suffix' => '</dt>',
      ];
      $info[$library_name]['description'] = [
        'content' => [
          '#markup' => $description,
        ],
        'status' => (!empty($library['deprecated'])) ? [
          '#markup' => $library['deprecated'],
          '#prefix' => '<div class="color-warning"><strong>',
          '#suffix' => '</strong></div>',
        ] : [],
        '#prefix' => '<dd>',
        '#suffix' => '</dd>',
      ];
    }

    // Description.
    $description = [
      'info' => $info,
    ];
    if (!$cli && $severity == REQUIREMENT_WARNING) {
      $description['cdn'] = ['#markup' => $this->t('<a href=":href">Disable CDN warning</a>', [':href' => Url::fromRoute('webform.config.advanced')->toString()])];
    }

    return [
      'webform_libraries' => [
        'title' => $this->t('Webform: External libraries'),
        'value' => $this->t('@total libraries (@installed installed; @excluded excluded; @missing CDN)', $stats),
        'description' => $this->renderer->renderPlain($description),
        'severity' => $severity,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibrary($name) {
    $libraries = $this->getLibraries();
    return $libraries[$name];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries($included = NULL) {
    // Initialize libraries.
    if (!isset($this->libraries)) {
      $this->libraries = $this->initLibraries();
    }

    $libraries = $this->libraries;
    if ($included !== NULL) {
      foreach ($libraries as $library_name => $library) {
        if ($this->isIncluded($library_name) !== $included) {
          unset($libraries[$library_name]);
        }
      }
    }
    return $libraries;
  }

  /**
   * {@inheritdoc}
   */
  public function getExcludedLibraries() {
    // Initialize excluded libraries.
    if (!isset($this->excludedLibraries)) {
      $this->excludedLibraries = $this->initExcludedLibraries();
    }

    return $this->excludedLibraries;
  }

  /**
   * {@inheritdoc}
   */
  public function isExcluded($name) {
    $excluded_libraries = $this->getExcludedLibraries();
    if (empty($excluded_libraries)) {
      return FALSE;
    }

    if (isset($excluded_libraries[$name])) {
      return TRUE;
    }

    if (strpos($name, 'libraries.') !== 0 && strpos($name, 'webform/libraries.') !== 0) {
      return FALSE;
    }

    $parts = explode('.', preg_replace('#^(webform/)?libraries.#', '', $name));
    while ($parts) {
      if (isset($excluded_libraries[implode('.', $parts)])) {
        return TRUE;
      }
      array_pop($parts);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isIncluded($name) {
    return !$this->isExcluded($name);
  }

  /**
   * Initialize libraries.
   *
   * @return array
   *   An associative array containing libraries.
   */
  protected function initLibraries() {
    $ckeditor_version = $this->getCkeditorVersion();

    $libraries = [];
    $libraries['ckeditor.autogrow'] = [
      'title' => $this->t('CKEditor: Autogrow'),
      'description' => $this->t('Automatically expand and shrink vertically depending on the amount and size of content entered in its editing area.'),
      'notes' => $this->t('Allows CKEditor to automatically expand and shrink vertically.'),
      'homepage_url' => Url::fromUri('https://ckeditor.com/addon/autogrow'),
      'download_url' => Url::fromUri("https://download.ckeditor.com/autogrow/releases/autogrow_$ckeditor_version.zip"),
      'plugin_path' => 'libraries/ckeditor.autogrow/',
      'plugin_url' => "https://cdn.rawgit.com/ckeditor/ckeditor-dev/$ckeditor_version/plugins/autogrow/",
      'version' => $ckeditor_version,
    ];
    $libraries['ckeditor.fakeobjects'] = [
      'title' => $this->t('CKEditor: Fake Objects'),
      'description' => $this->t('Utility required by CKEditor link plugin.'),
      'notes' => $this->t('Allows CKEditor to use basic image and link dialog.'),
      'homepage_url' => Url::fromUri('https://ckeditor.com/addon/fakeobjects'),
      'download_url' => Url::fromUri("https://download.ckeditor.com/fakeobjects/releases/fakeobjects_$ckeditor_version.zip"),
      'plugin_path' => 'libraries/ckeditor.fakeobjects/',
      'plugin_url' => "https://cdn.rawgit.com/ckeditor/ckeditor-dev/$ckeditor_version/plugins/fakeobjects/",
      'version' => $ckeditor_version,
    ];
    $libraries['ckeditor.image'] = [
      'title' => $this->t('CKEditor: Image'),
      'description' => $this->t('Provides a basic image dialog for CKEditor.'),
      'notes' => $this->t('Allows CKEditor to use basic image dialog, which is not included in Drupal core.'),
      'homepage_url' => Url::fromUri('https://ckeditor.com/addon/image'),
      'download_url' => Url::fromUri("https://download.ckeditor.com/image/releases/image_$ckeditor_version.zip"),
      'plugin_path' => 'libraries/ckeditor.image/',
      'plugin_url' => "https://cdn.rawgit.com/ckeditor/ckeditor-dev/$ckeditor_version/plugins/image/",
      'version' => $ckeditor_version,
    ];
    $libraries['ckeditor.link'] = [
      'title' => $this->t('CKEditor: Link'),
      'description' => $this->t('Provides a basic link dialog for CKEditor.'),
      'notes' => $this->t('Allows CKEditor to use basic link dialog, which is not included in Drupal core.'),
      'homepage_url' => Url::fromUri('https://ckeditor.com/addon/link'),
      'download_url' => Url::fromUri("https://download.ckeditor.com/link/releases/link_$ckeditor_version.zip"),
      'plugin_path' => 'libraries/ckeditor.link/',
      'plugin_url' => "https://cdn.rawgit.com/ckeditor/ckeditor-dev/$ckeditor_version/plugins/link/",
      'version' => $ckeditor_version,
    ];
    $libraries['ckeditor.codemirror'] = [
      'title' => $this->t('CKEditor: CodeMirror'),
      'description' => $this->t('Provides syntax highlighting for the CKEditor with the CodeMirror Plugin.'),
      'notes' => $this->t('Makes it easier to edit the HTML source.'),
      'homepage_url' => Url::fromUri('https://github.com/w8tcha/CKEditor-CodeMirror-Plugin'),
      'download_url' => Url::fromUri('https://github.com/w8tcha/CKEditor-CodeMirror-Plugin/releases/download/v1.17.12/CKEditor-CodeMirror-Plugin.zip'),
      'plugin_path' => 'libraries/ckeditor.codemirror/codemirror/',
      'plugin_url' => "https://cdn.rawgit.com/w8tcha/CKEditor-CodeMirror-Plugin/v1.17.12/codemirror/",
      'version' => 'v1.17.12',
    ];
    $libraries['codemirror'] = [
      'title' => $this->t('Code Mirror'),
      'description' => $this->t('Code Mirror is a versatile text editor implemented in JavaScript for the browser.'),
      'notes' => $this->t('Code Mirror is used to provide a text editor for YAML, HTML, CSS, and JavaScript configuration settings and messages.'),
      'homepage_url' => Url::fromUri('http://codemirror.net/'),
      'download_url' => Url::fromUri('https://github.com/components/codemirror/archive/5.51.0.zip'),
      'issues_url' => Url::fromUri('https://github.com/codemirror/codemirror/issues'),
      'version' => '5.51.0',
    ];
    $libraries['algolia.places'] = [
      'title' => $this->t('Algolia Places'),
      'description' => $this->t('Algolia Places provides a fast, distributed and easy way to use an address search autocomplete JavaScript library on your website.'),
      'notes' => $this->t('Algolia Places is by the location places elements.'),
      'homepage_url' => Url::fromUri('https://github.com/algolia/places'),
      'issues_url' => Url::fromUri('https://github.com/algolia/places/issues'),
      // NOTE: Using NPM/JsDelivr because it contains the '/dist/cdn/' directory.
      // @see https://asset-packagist.org/package/detail?fullname=npm-asset/places.js
      // @see https://www.jsdelivr.com/package/npm/places.js
      'download_url' => Url::fromUri('https://registry.npmjs.org/places.js/-/places.js-1.17.1.tgz'),
      'version' => '1.17.1',
      'elements' => ['webform_location_places'],
    ];
    $libraries['jquery.inputmask'] = [
      'title' => $this->t('jQuery: Input Mask'),
      'description' => $this->t('Input masks ensures a predefined format is entered. This can be useful for dates, numerics, phone numbers, etc…'),
      'notes' => $this->t('Input masks are used to ensure predefined and custom formats for text fields.'),
      'homepage_url' => Url::fromUri('https://robinherbots.github.io/Inputmask/'),
      'download_url' => Url::fromUri('https://github.com/RobinHerbots/jquery.inputmask/archive/5.0.3.zip'),
      'version' => '5.0.3',
    ];
    $libraries['jquery.intl-tel-input'] = [
      'title' => $this->t('jQuery: International Telephone Input'),
      'description' => $this->t("A jQuery plugin for entering and validating international telephone numbers. It adds a flag dropdown to any input, detects the user's country, displays a relevant placeholder and provides formatting/validation methods."),
      'notes' => $this->t('International Telephone Input is used by the Telephone element.'),
      'homepage_url' => Url::fromUri('https://github.com/jackocnr/intl-tel-input'),
      'download_url' => Url::fromUri('https://github.com/jackocnr/intl-tel-input/archive/v16.1.0.zip'),
      'version' => '16.1.0',
    ];
    $libraries['jquery.rateit'] = [
      'title' => $this->t('jQuery: RateIt'),
      'description' => $this->t("Rating plugin for jQuery. Fast, progressive enhancement, touch support, customizable (just swap out the images, or change some CSS), unobtrusive JavaScript (using HTML5 data-* attributes), RTL support. The Rating plugin supports as many stars as you'd like, and also any step size."),
      'notes' => $this->t('RateIt is used to provide a customizable rating element.'),
      'homepage_url' => Url::fromUri('https://github.com/gjunge/rateit.js'),
      'download_url' => Url::fromUri('https://github.com/gjunge/rateit.js/archive/1.1.3.zip'),
      'version' => '1.1.3',
      'elements' => ['webform_rating'],
    ];
    $libraries['jquery.textcounter'] = [
      'title' => $this->t('jQuery: Text Counter'),
      'description' => $this->t('A jQuery plugin for counting and limiting characters/words on text input, or textarea, elements.'),
      'notes' => $this->t('Word or character counting, with server-side validation, is available for text fields and text areas.'),
      'homepage_url' => Url::fromUri('https://github.com/ractoon/jQuery-Text-Counter'),
      'download_url' => Url::fromUri('https://github.com/ractoon/jQuery-Text-Counter/archive/0.8.0.zip'),
      'version' => '0.8.0',
    ];
    $libraries['jquery.timepicker'] = [
      'title' => $this->t('jQuery: Timepicker'),
      'description' => $this->t('A lightweight, customizable javascript timepicker plugin for jQuery, inspired by Google Calendar.'),
      'notes' => $this->t('Timepicker is used to provide a polyfill for HTML 5 time elements.'),
      'homepage_url' => Url::fromUri('https://github.com/jonthornton/jquery-timepicker'),
      'download_url' => Url::fromUri('https://github.com/jonthornton/jquery-timepicker/archive/1.13.0.zip'),
      'version' => '1.13.0',
    ];
    $libraries['progress-tracker'] = [
      'title' => $this->t('Progress Tracker'),
      'description' => $this->t("A flexible SASS component to illustrate the steps in a multi-step process e.g. a multi-step form, a timeline or a quiz."),
      'notes' => $this->t('Progress Tracker is used by multi-step wizard forms.'),
      'homepage_url' => Url::fromUri('http://nigelotoole.github.io/progress-tracker/'),
      'download_url' => Url::fromUri('https://github.com/NigelOToole/progress-tracker/archive/v1.4.0.zip'),
      'version' => '1.4.0',
    ];
    $libraries['signature_pad'] = [
      'title' => $this->t('Signature Pad'),
      'description' => $this->t("Signature Pad is a JavaScript library for drawing smooth signatures. It is HTML5 canvas based and uses variable width Bézier curve interpolation. It works in all modern desktop and mobile browsers and doesn't depend on any external libraries."),
      'notes' => $this->t('Signature Pad is used to provide a signature element.'),
      'homepage_url' => Url::fromUri('https://github.com/szimek/signature_pad'),
      'download_url' => Url::fromUri('https://github.com/szimek/signature_pad/archive/v2.3.0.zip'),
      'version' => '2.3.0',
      'elements' => ['webform_signature'],
    ];
    $libraries['jquery.select2'] = [
      'title' => $this->t('jQuery: Select2'),
      'description' => $this->t('Select2 gives you a customizable select box with support for searching and tagging.'),
      'notes' => $this->t('Select2 is used to improve the user experience for select menus. Select2 is the recommended select menu enhancement library.'),
      'homepage_url' => Url::fromUri('https://select2.github.io/'),
      'download_url' => Url::fromUri('https://github.com/select2/select2/archive/4.0.12.zip'),
      'version' => '4.0.12',
      'module' => $this->moduleHandler->moduleExists('select2') ? 'select2' : '',
    ];
    $libraries['choices'] = [
      'title' => $this->t('Choices'),
      'description' => $this->t('Choices.js is a lightweight, configurable select box/text input plugin. Similar to Select2 and Selectize but without the jQuery dependency.'),
      'notes' => $this->t('Choices.js is used to improve the user experience for select menus. Choices.js is an alternative to Select2.'),
      'homepage_url' => Url::fromUri('https://joshuajohnson.co.uk/Choices/'),
      'download_url' => Url::fromUri('https://github.com/jshjohnson/Choices/archive/v9.0.1.zip'),
      'version' => '9.0.1',
    ];
    $libraries['jquery.chosen'] = [
      'title' => $this->t('jQuery: Chosen'),
      'description' => $this->t('A jQuery plugin that makes long, unwieldy select boxes much more user-friendly.'),
      'notes' => $this->t('Chosen is used to improve the user experience for select menus. Chosen is an alternative to Select2.'),
      'homepage_url' => Url::fromUri('https://harvesthq.github.io/chosen/'),
      'download_url' => Url::fromUri('https://github.com/harvesthq/chosen/releases/download/v1.8.7/chosen_v1.8.7.zip'),
      'version' => '1.8.7',
      'module' => $this->moduleHandler->moduleExists('chosen') ? 'chosen' : '',
    ];

    // Add webform as the provider to all libraries.
    foreach ($libraries as $library_name => $library) {
      $libraries[$library_name] += [
        'optional' => TRUE,
        'provider' => 'webform',
      ];
    }

    // Allow other modules to define webform libraries.
    foreach ($this->moduleHandler->getImplementations('webform_libraries_info') as $module) {
      foreach ($this->moduleHandler->invoke($module, 'webform_libraries_info') as $library_name => $library) {
        $libraries[$library_name] = $library + [
          'provider' => $module,
        ];
      }
    }

    // Allow other modules to alter webform libraries.
    $this->moduleHandler->alter('webform_libraries_info', $libraries);

    // Sort libraries by key.
    ksort($libraries);

    // Move deprecated libraries last.
    foreach ($libraries as $library_name => $library) {
      if (!empty($library['deprecated'])) {
        unset($libraries[$library_name]);
        $libraries[$library_name] = $library;
      }
    }

    return $libraries;
  }

  /**
   * Initialize excluded libraries.
   *
   * @return array
   *   A key array containing excluded libraries.
   */
  protected function initExcludedLibraries() {
    // Get excluded optional libraries.
    if ($excluded_libraries = $this->configFactory->get('webform.settings')->get('libraries.excluded_libraries')) {
      $excluded_libraries = array_combine($excluded_libraries, $excluded_libraries);
    }
    else {
      $excluded_libraries = [];
    }

    // Get excluded libraries based on excluded (element) types.
    $libraries = $this->getLibraries();
    foreach ($libraries as $library_name => $library) {
      if (!empty($library['elements']) && $this->areElementsExcluded($library['elements'])) {
        $excluded_libraries[$library_name] = $library_name;
      }
    }

    return $excluded_libraries;
  }

  /**
   * Determine if a library's elements are excluded.
   *
   * @param array $elements
   *   An array of element types.
   *
   * @return bool
   *   TRUE if a library's elements are excluded.
   */
  protected function areElementsExcluded(array $elements) {
    $excluded_elements = $this->configFactory->get('webform.settings')->get('element.excluded_elements');
    if (!$excluded_elements) {
      return FALSE;
    }
    return WebformArrayHelper::keysExist($excluded_elements, $elements);
  }

  /**
   * Get Drupal core's CKEditor version number.
   *
   * @return string
   *   Drupal core's CKEditor version number.
   */
  protected function getCkeditorVersion() {
    // Get CKEditor semantic version number from the JS file.
    // @see core/core.libraries.yml
    $definition = $this->libraryDiscovery->getLibraryByName('core', 'ckeditor');
    $ckeditor_version = $definition['js'][0]['version'];

    // Parse CKEditor semantic version number from security patches
    // (i.e. 4.8.0+2018-04-18-security-patch).
    if (preg_match('/^\d+\.\d+\.\d+/', $ckeditor_version, $match)) {
      return $match[0];
    }
    else {
      return $ckeditor_version;
    }
  }

}
