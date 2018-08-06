<?php

namespace Drupal\webform;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\webform\Utility\WebformArrayHelper;

/**
 * Webform libraries manager.
 */
class WebformLibrariesManager implements WebformLibrariesManagerInterface {

  use StringTranslationTrait;

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
  protected $excludedLibraries = [];

  /**
   * Constructs a WebformLibrariesManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, RendererInterface $renderer) {
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->renderer = $renderer;

    $this->libraries = $this->initLibraries();
    $this->excludedLibraries = $this->initExcludedLibraries();
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

      if ($this->isExcluded($library_name)) {
        // Excluded.
        $stats['@excluded']++;
        $title = $this->t('<strong>@title</strong> (Excluded)', $t_args);
        if (!empty($library['elements']) && $this->areElementsExcluded($library['elements'])) {
          $t_args['@element_type'] = implode('; ', $library['elements']);
          $description = $this->t('The <a href=":homepage_href">@title</a> library is excluded because required element types (@element_type) are <a href=":settings_elements_href">excluded</a>.', $t_args);
        }
        else {
          $description = $this->t('The <a href=":homepage_href">@title</a> library is <a href=":settings_libraries_href">excluded</a>.', $t_args);
        }
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

      $info[$library_name] = [
        'title' => [
          '#markup' => $title,
          '#prefix' => '<dt>',
          '#suffix' => '</dt>',
        ],
        'description' => [
          '#markup' => $description,
          '#prefix' => '<dd>',
          '#suffix' => '</dd>',
        ],
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
    return $this->libraries[$name];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries($included = NULL) {
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
    return $this->excludedLibraries;
  }

  /**
   * {@inheritdoc}
   */
  public function isExcluded($name) {
    if (empty($this->excludedLibraries)) {
      return FALSE;
    }

    if (isset($this->excludedLibraries[$name])) {
      return TRUE;
    }

    if (strpos($name, 'libraries.') !== 0 && strpos($name, 'webform/libraries.') !== 0) {
      return FALSE;
    }

    $parts = explode('.', preg_replace('#^(webform/)?libraries.#', '', $name));
    while ($parts) {
      if (isset($this->excludedLibraries[implode('.', $parts)])) {
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
    // Get Drupal core's CKEditor version number.
    $core_libraries = Yaml::decode(file_get_contents('core/core.libraries.yml'));
    $ckeditor_version = $core_libraries['ckeditor']['version'];

    $libraries = [];
    $libraries['ckeditor.autogrow'] = [
      'title' => $this->t('CKEditor: Autogrow'),
      'description' => $this->t('Automatically expand and shrink vertically depending on the amount and size of content entered in its editing area.'),
      'notes' => $this->t('Allows CKEditor to automatically expand and shrink vertically.'),
      'homepage_url' => Url::fromUri('https://ckeditor.com/addon/autogrow'),
      'download_url' => Url::fromUri("https://download.ckeditor.com/autogrow/releases/autogrow_$ckeditor_version.zip"),
      'version' => $ckeditor_version,
      'optional' => TRUE,
    ];
    $libraries['ckeditor.fakeobjects'] = [
      'title' => $this->t('CKEditor: Fakeobjects'),
      'description' => $this->t('Utility required by CKEditor link plugin.'),
      'notes' => $this->t('Allows CKEditor to use basic image and link dialog.'),
      'homepage_url' => Url::fromUri('https://ckeditor.com/addon/fakeobjects'),
      'download_url' => Url::fromUri("https://download.ckeditor.com/fakeobjects/releases/fakeobjects_$ckeditor_version.zip"),
      'version' => $ckeditor_version,
      'optional' => TRUE,
    ];
    $libraries['ckeditor.image'] = [
      'title' => $this->t('CKEditor: Image'),
      'description' => $this->t('Provides a basic image dialog for CKEditor.'),
      'notes' => $this->t('Allows CKEditor to use basic image dialog, which is not included in Drupal core.'),
      'homepage_url' => Url::fromUri('https://ckeditor.com/addon/image'),
      'download_url' => Url::fromUri("https://download.ckeditor.com/image/releases/image_$ckeditor_version.zip"),
      'version' => $ckeditor_version,
      'optional' => TRUE,
    ];
    $libraries['ckeditor.link'] = [
      'title' => $this->t('CKEditor: Link'),
      'description' => $this->t('Provides a basic link dialog for CKEditor.'),
      'notes' => $this->t('Allows CKEditor to use basic link dialog, which is not included in Drupal core.'),
      'homepage_url' => Url::fromUri('https://ckeditor.com/addon/link'),
      'download_url' => Url::fromUri('https://download.ckeditor.com/link/releases/link_4.6.2.zip'),
      'version' => '4.6.2',
      'optional' => TRUE,
    ];
    $libraries['codemirror'] = [
      'title' => $this->t('Code Mirror'),
      'description' => $this->t('Code Mirror is a versatile text editor implemented in JavaScript for the browser.'),
      'notes' => $this->t('Code Mirror is used to provide a text editor for YAML, HTML, CSS, and JavaScript configuration settings and messages.'),
      'homepage_url' => Url::fromUri('http://codemirror.net/'),
      'download_url' => Url::fromUri('https://github.com/components/codemirror/archive/5.27.4.zip'),
      'version' => '5.27.4',
      'optional' => TRUE,
    ];
    $libraries['jquery.geocomplete'] = [
      'title' => $this->t('jQuery: Geocoding and Places Autocomplete Plugin'),
      'description' => $this->t("Geocomple is an advanced jQuery plugin that wraps the Google Maps API's Geocoding and Places Autocomplete services."),
      'notes' => $this->t('Geocomplete is used by the location element.'),
      'homepage_url' => Url::fromUri('http://ubilabs.github.io/geocomplete/'),
      'download_url' => Url::fromUri('https://github.com/ubilabs/geocomplete/archive/1.7.0.zip'),
      'version' => '1.7.0',
      'elements' => ['webform_location'],
    ];
    $libraries['jquery.icheck'] = [
      'title' => $this->t('jQuery: iCheck'),
      'description' => $this->t('Highly customizable checkboxes and radio buttons.'),
      'notes' => $this->t('iCheck is used to optionally enhance checkboxes and radio buttons.'),
      'homepage_url' => Url::fromUri('http://icheck.fronteed.com/'),
      'download_url' => Url::fromUri('https://github.com/fronteed/icheck/archive/1.0.2.zip'),
      'version' => '1.0.2 ',
      'optional' => TRUE,
    ];
    $libraries['jquery.image-picker'] = [
      'title' => $this->t('jQuery: Image Picker'),
      'description' => $this->t('A simple jQuery plugin that transforms a select element into a more user friendly graphical interface.'),
      'notes' => $this->t('Image Picker is used by the Image select element.'),
      'homepage_url' => Url::fromUri('https://rvera.github.io/image-picker/'),
      'download_url' => Url::fromUri('https://github.com/rvera/image-picker/archive/0.3.0.zip'),
      'version' => '0.3.0',
      'elements' => ['webform_image_select'],
    ];
    $libraries['jquery.inputmask'] = [
      'title' => $this->t('jQuery: Input Mask'),
      'description' => $this->t('Input masks ensures a predefined format is entered. This can be useful for dates, numerics, phone numbers, etc...'),
      'notes' => $this->t('Input masks are used to ensure predefined and custom formats for text fields.'),
      'homepage_url' => Url::fromUri('https://robinherbots.github.io/Inputmask/'),
      'download_url' => Url::fromUri('https://github.com/RobinHerbots/jquery.inputmask/archive/3.3.7.zip'),
      'version' => '3.3.7',
      'optional' => TRUE,
    ];
    $libraries['jquery.intl-tel-input'] = [
      'title' => $this->t('jQuery: International Telephone Input'),
      'description' => $this->t("A jQuery plugin for entering and validating international telephone numbers. It adds a flag dropdown to any input, detects the user's country, displays a relevant placeholder and provides formatting/validation methods."),
      'notes' => $this->t('International Telephone Input is used by the Telephone element.'),
      'homepage_url' => Url::fromUri('https://github.com/jackocnr/intl-tel-input'),
      'download_url' => Url::fromUri('https://github.com/jackocnr/intl-tel-input/archive/v12.0.0.zip'),
      'version' => '12.0.0',
      'optional' => TRUE,
    ];
    $libraries['jquery.rateit'] = [
      'title' => $this->t('jQuery: RateIt'),
      'description' => $this->t("Rating plugin for jQuery. Fast, progressive enhancement, touch support, customizable (just swap out the images, or change some CSS), unobtrusive JavaScript (using HTML5 data-* attributes), RTL support. The Rating plugin supports as many stars as you'd like, and also any step size."),
      'notes' => $this->t('RateIt is used to provide a customizable rating webform element.'),
      'homepage_url' => Url::fromUri('https://github.com/gjunge/rateit.js'),
      'download_url' => Url::fromUri('https://github.com/gjunge/rateit.js/archive/1.1.1.zip'),
      'version' => '1.1.1',
      'elements' => ['webform_rating'],
    ];
    $libraries['jquery.select2'] = [
      'title' => $this->t('jQuery: Select2'),
      'description' => $this->t('Select2 gives you a customizable select box with support for searching and tagging.'),
      'notes' => $this->t('Select2 is used to improve the user experience for select menus. Select2 is the recommended select menu enhancement library.'),
      'homepage_url' => Url::fromUri('https://select2.github.io/'),
      'download_url' => Url::fromUri('https://github.com/select2/select2/archive/4.0.3.zip'),
      'version' => '4.0.3',
      'optional' => TRUE,
    ];
    $libraries['jquery.chosen'] = [
      'title' => $this->t('jQuery: Chosen'),
      'description' => $this->t('A jQuery plugin that makes long, unwieldy select boxes much more user-friendly.'),
      'notes' => $this->t('Chosen is used to improve the user experience for select menus. Chosen is an alternative to Select2.'),
      'homepage_url' => Url::fromUri('https://harvesthq.github.io/chosen/'),
      'download_url' => Url::fromUri('https://github.com/harvesthq/chosen/releases/download/v1.7.0/chosen_v1.7.0.zip'),
      'version' => '1.7.0',
      'optional' => TRUE,
    ];
    $libraries['jquery.timepicker'] = [
      'title' => $this->t('jQuery: Timepicker'),
      'description' => $this->t('A lightweight, customizable javascript timepicker plugin for jQuery, inspired by Google Calendar.'),
      'notes' => $this->t('Timepicker is used to provide a polyfill for HTML 5 time elements.'),
      'homepage_url' => Url::fromUri('https://github.com/jonthornton/jquery-timepicker'),
      'download_url' => Url::fromUri('https://github.com/jonthornton/jquery-timepicker/archive/1.11.11.zip'),
      'version' => '1.11.11 ',
      'optional' => TRUE,
    ];
    $libraries['jquery.toggles'] = [
      'title' => $this->t('jQuery: Toggles'),
      'description' => $this->t('Toggles is a lightweight jQuery plugin that creates easy-to-style toggle buttons.'),
      'notes' => $this->t('Toggles is used to provide a toggle element.'),
      'homepage_url' => Url::fromUri('https://github.com/simontabor/jquery-toggles/'),
      'download_url' => Url::fromUri('https://github.com/simontabor/jquery-toggles/archive/v4.0.0.zip'),
      'version' => '4.0.0',
      'elements' => ['webform_toggle', 'webform_toggles'],
    ];
    $libraries['jquery.word-and-character-counter'] = [
      'title' => $this->t('jQuery: Word and character counter plug-in!'),
      'description' => $this->t('The jQuery word and character counter plug-in allows you to count characters or words'),
      'notes' => $this->t('Word or character counting, with server-side validation, is available for text fields and text areas.'),
      'homepage_url' => Url::fromUri('https://github.com/qwertypants/jQuery-Word-and-Character-Counter-Plugin'),
      'download_url' => Url::fromUri('https://github.com/qwertypants/jQuery-Word-and-Character-Counter-Plugin/archive/2.5.1.zip'),
      'version' => '2.5.1',
      'optional' => TRUE,
    ];
    $libraries['progress-tracker'] = [
      'title' => $this->t('Progress Tracker'),
      'description' => $this->t("A flexible SASS component to illustrate the steps in a multi step process e.g. a multi step form, a timeline or a quiz."),
      'notes' => $this->t('Progress Tracker is used by multi-step wizard forms.'),
      'homepage_url' => Url::fromUri('http://nigelotoole.github.io/progress-tracker/'),
      'download_url' => Url::fromUri('https://github.com/NigelOToole/progress-tracker/archive/v1.4.0.zip'),
      'version' => '1.4.0',
      'optional' => TRUE,
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
    foreach ($this->libraries as $library_name => $library) {
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

}
