<?php

namespace Drupal\image_captcha\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays the pants settings form.
 */
class ImageCaptchaSettingsForm extends ConfigFormBase {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a \Drupal\image_captcha\Form\ImageCaptchaSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LanguageManagerInterface $language_manager) {
    parent::__construct($config_factory);
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'image_captcha_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['image_captcha.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('image_captcha.settings');
    // Add CSS and JS for theming and added usability on admin form.
    $form['#attached']['library'][] = 'captcha_image/base';

    // First some error checking.
    $setup_status = _image_captcha_check_setup(FALSE);
    if ($setup_status & IMAGE_CAPTCHA_ERROR_NO_GDLIB) {
      drupal_set_message($this->t(
        'The Image CAPTCHA module can not generate images because your PHP setup does not support it (no <a href="!gdlib" target="_blank">GD library</a> with JPEG support).',
        ['!gdlib' => 'http://php.net/manual/en/book.image.php']
      ), 'error');
      // It is no use to continue building the rest of the settings form.
      return $form;
    }

    $form['image_captcha_example'] = [
      '#type' => 'details',
      '#title' => $this->t('Example'),
      '#description' => $this->t('Presolved image CAPTCHA example, generated with the current settings.'),
    ];

    $form['image_captcha_example']['image'] = [
      '#type' => 'captcha',
      '#captcha_type' => 'image_captcha/Image',
      '#captcha_admin_mode' => TRUE,
    ];

    // General code settings.
    $form['image_captcha_code_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Code settings'),
    ];

    $form['image_captcha_code_settings']['image_captcha_image_allowed_chars'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Characters to use in the code'),
      '#default_value' => $config->get('image_captcha_image_allowed_chars'),
    ];
    $form['image_captcha_code_settings']['image_captcha_code_length'] = [
      '#type' => 'select',
      '#title' => $this->t('Code length'),
      '#options' => [2 => 2, 3, 4, 5, 6, 7, 8, 9, 10],
      '#default_value' => $config->get('image_captcha_code_length'),
      '#description' => $this->t('The code length influences the size of the image. Note that larger values make the image generation more CPU intensive.'),
    ];
    // RTL support option (only show this option when there are RTL languages).
    $language = $this->languageManager->getCurrentLanguage();
    if ($language->getDirection() == Language::DIRECTION_RTL) {
      $form['image_captcha_code_settings']['image_captcha_rtl_support'] = [
        '#title' => $this->t('RTL support'),
        '#type' => 'checkbox',
        '#default_value' => $config->get('image_captcha_rtl_support'),
        '#description' => $this->t('Enable this option to render the code from right to left for right to left languages.'),
      ];
    }

    // Font related stuff.
    $form['image_captcha_font_settings'] = $this->settingsDotSection();

    // Color and file format settings.
    $form['image_captcha_color_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Color and image settings'),
      '#description' => $this->t('Configuration of the background, text colors and file format of the image CAPTCHA.'),
    ];

    $form['image_captcha_color_settings']['image_captcha_background_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Background color'),
      '#description' => $this->t('Enter the hexadecimal code for the background color (e.g. #FFF or #FFCE90). When using the PNG file format with transparent background, it is recommended to set this close to the underlying background color.'),
      '#default_value' => $config->get('image_captcha_background_color'),
      '#maxlength' => 7,
      '#size' => 8,
    ];
    $form['image_captcha_color_settings']['image_captcha_foreground_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text color'),
      '#description' => $this->t('Enter the hexadecimal code for the text color (e.g. #000 or #004283).'),
      '#default_value' => $config->get('image_captcha_foreground_color'),
      '#maxlength' => 7,
      '#size' => 8,
    ];
    $form['image_captcha_color_settings']['image_captcha_foreground_color_randomness'] = [
      '#type' => 'select',
      '#title' => $this->t('Additional variation of text color'),
      '#options' => [
        0 => $this->t('No variation'),
        50 => $this->t('Little variation'),
        100 => $this->t('Medium variation'),
        150 => $this->t('High variation'),
        200 => $this->t('Very high variation'),
      ],
      '#default_value' => $config->get('image_captcha_foreground_color_randomness'),
      '#description' => $this->t('The different characters will have randomized colors in the specified range around the text color.'),
    ];
    $form['image_captcha_color_settings']['image_captcha_file_format'] = [
      '#type' => 'select',
      '#title' => $this->t('File format'),
      '#description' => $this->t('Select the file format for the image. JPEG usually results in smaller files, PNG allows tranparency.'),
      '#default_value' => $config->get('image_captcha_file_format'),
      '#options' => [
        IMAGE_CAPTCHA_FILE_FORMAT_JPG => $this->t('JPEG'),
        IMAGE_CAPTCHA_FILE_FORMAT_PNG => $this->t('PNG'),
        IMAGE_CAPTCHA_FILE_FORMAT_TRANSPARENT_PNG => $this->t('PNG with transparent background'),
      ],
    ];

    // Distortion and noise settings.
    $form['image_captcha_distortion_and_noise'] = [
      '#type' => 'details',
      '#title' => $this->t('Distortion and noise'),
      '#description' => $this->t('With these settings you can control the degree of obfuscation by distortion and added noise. Do not exaggerate the obfuscation and assure that the code in the image is reasonably readable. For example, do not combine high levels of distortion and noise.'),
    ];

    $form['image_captcha_distortion_and_noise']['image_captcha_distortion_amplitude'] = [
      '#type' => 'select',
      '#title' => $this->t('Distortion level'),
      '#options' => [
        0 => $this->t('@level - no distortion', ['@level' => '0']),
        1 => $this->t('@level - low', ['@level' => '1']),
        2 => '2',
        3 => '3',
        4 => '4',
        5 => $this->t('@level - medium', ['@level' => '5']),
        6 => '6',
        7 => '7',
        8 => '8',
        9 => '9',
        10 => $this->t('@level - high', ['@level' => '10']),
      ],
      '#default_value' => $config->get('image_captcha_distortion_amplitude'),
      '#description' => $this->t('Set the degree of wave distortion in the image.'),
    ];
    $form['image_captcha_distortion_and_noise']['image_captcha_bilinear_interpolation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Smooth distortion'),
      '#default_value' => $config->get('image_captcha_bilinear_interpolation'),
      '#description' => $this->t('This option enables bilinear interpolation of the distortion which makes the image look smoother, but it is more CPU intensive.'),
    ];

    $form['image_captcha_distortion_and_noise']['image_captcha_dot_noise'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add salt and pepper noise'),
      '#default_value' => $config->get('image_captcha_dot_noise'),
      '#description' => $this->t('This option adds randomly colored point noise.'),
    ];

    $form['image_captcha_distortion_and_noise']['image_captcha_line_noise'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add line noise'),
      '#default_value' => $config->get('image_captcha_line_noise', 0),
      '#description' => $this->t('This option enables lines randomly drawn on top of the text code.'),
    ];

    $form['image_captcha_distortion_and_noise']['image_captcha_noise_level'] = [
      '#type' => 'select',
      '#title' => $this->t('Noise level'),
      '#options' => [
        1 => '1 - ' . $this->t('low'),
        2 => '2',
        3 => '3 - ' . $this->t('medium'),
        4 => '4',
        5 => '5 - ' . $this->t('high'),
        7 => '7',
        10 => '10 - ' . $this->t('severe'),
      ],
      '#default_value' => (int) $config->get('image_captcha_noise_level'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Check image_captcha_image_allowed_chars for spaces.
    if (preg_match('/\s/', $form_state->getValue('image_captcha_image_allowed_chars'))) {
      $form_state->setErrorByName('image_captcha_image_allowed_chars', $this->t('The list of characters to use should not contain spaces.'));
    }

    if (!isset($form['image_captcha_font_settings']['no_ttf_support'])) {
      // Check the selected fonts.
      // Filter the image_captcha fonts array to pick out the selected ones.
      $fonts = array_filter($form_state->getValue('image_captcha_fonts'));
      if (count($fonts) < 1) {
        $form_state->setErrorByName('image_captcha_fonts', $this->t('You need to select at least one font.'));
      }
      if ($form_state->getValue('image_captcha_fonts')['BUILTIN']) {
        // With the built in font, only latin2 characters should be used.
        if (preg_match('/[^a-zA-Z0-9]/', $form_state->getValue('image_captcha_image_allowed_chars'))) {
          $form_state->setErrorByName('image_captcha_image_allowed_chars', $this->t('The built-in font only supports Latin2 characters. Only use "a" to "z" and numbers.'));
        }
      }

      $readable_fonts = [];
      list($readable_fonts, $problem_fonts) = _image_captcha_check_fonts($fonts);
      if (count($problem_fonts) > 0) {
        $form_state->setErrorByName('image_captcha_fonts', $this->t('The following fonts are not readable: %fonts.', ['%fonts' => implode(', ', $problem_fonts)]));
      }
    }

    // Check color settings.
    if (!preg_match('/^#([0-9a-fA-F]{3}){1,2}$/', $form_state->getValue('image_captcha_background_color'))) {
      $form_state->setErrorByName('image_captcha_background_color', $this->t('Background color is not a valid hexadecimal color value.'));
    }
    if (!preg_match('/^#([0-9a-fA-F]{3}){1,2}$/', $form_state->getValue('image_captcha_foreground_color'))) {
      $form_state->setErrorByName('image_captcha_foreground_color', $this->t('Text color is not a valid hexadecimal color value.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!isset($form['image_captcha_font_settings']['no_ttf_support'])) {
      // Filter the image_captcha fonts array to pick out the selected ones.
      $fonts = array_filter($form_state->getValue('image_captcha_fonts'));
      $this->config('image_captcha.settings')
        ->set('image_captcha_fonts', $fonts)
        ->save();
    }

    parent::SubmitForm($form, $form_state);
  }

  /**
   * Form elements for the font specific setting.
   *
   * This is refactored to a separate function to avoid polluting the
   * general form function image_captcha_settings_form with some
   * specific logic.
   *
   * @return array
   *   The font settings specific form elements.
   */
  protected function settingsDotSection() {
    $config = $this->config('image_captcha.settings');
    // Put it all in a details element.
    $form = [
      '#type' => 'details',
      '#title' => $this->t('Font settings'),
    ];

    // First check if there is TrueType support.
    $setup_status = _image_captcha_check_setup(FALSE);
    if ($setup_status & IMAGE_CAPTCHA_ERROR_NO_TTF_SUPPORT) {
      // Show a warning that there is no TrueType support.
      $form['no_ttf_support'] = [
        '#type' => 'item',
        '#title' => $this->t('No TrueType support'),
        '#markup' => $this->t('The Image CAPTCHA module can not use TrueType fonts because your PHP setup does not support it. You can only use a PHP built-in bitmap font of fixed size.'),
      ];
    }
    else {
      // Build a list of  all available fonts.
      $available_fonts = [];

      // List of folders to search through for TrueType fonts.
      $fonts = $this->getAvailableFontsFromDirectories();
      // Cache the list of previewable fonts. All the previews are done
      // in separate requests, and we don't want to rescan the filesystem
      // every time, so we cache the result.
      $config->set('image_captcha_fonts_preview_map_cache', $fonts);
      $config->save();
      // Put these fonts with preview image in the list.
      foreach ($fonts as $token => $font) {

        $title = t('Font preview of @font (@file)', [
          '@font' => $font['name'],
          '@file' => $font['uri'],
        ]);
        $attributes = [
          'src' => Url::fromRoute('image_captcha.font_preview', ['token' => $token])
            ->toString(),
          'title' => $title,
          'alt' => $title,
        ];
        $available_fonts[$font['uri']] = '<img' . new Attribute($attributes) . ' />';
      }

      // Append the PHP built-in font at the end.
      $title = t('Preview of built-in font');
      $attributes = [
        'src' => Url::fromRoute('image_captcha.font_preview', ['token' => 'BUILTIN'])
          ->toString(),
        'alt' => $title,
        'title' => $title,
      ];
      $available_fonts['BUILTIN'] = (string) t('PHP built-in font: font_preview', [
        'font_preview' => '<img' . new Attribute($attributes) . ' />',
      ]);

      $default_fonts = _image_captcha_get_enabled_fonts();
      $conf_path = DrupalKernel::findSitePath($this->getRequest());

      $form['image_captcha_fonts'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Fonts'),
        '#default_value' => $default_fonts,
        '#description' => $this->t('Select the fonts to use for the text in the image CAPTCHA. Apart from the provided defaults, you can also use your own TrueType fonts (filename extension .ttf) by putting them in %fonts_library_general or %fonts_library_specific.',
          [
            '%fonts_library_general' => 'sites/all/libraries/fonts',
            '%fonts_library_specific' => $conf_path . '/libraries/fonts',
          ]
        ),
        '#options' => $available_fonts,
        '#attributes' => ['class' => ['image_captcha_admin_fonts_selection']],
      ];

      $form['image_captcha_font_size'] = [
        '#type' => 'select',
        '#title' => $this->t('Font size'),
        '#options' => [
          9 => '9 pt - ' . $this->t('tiny'),
          12 => '12 pt - ' . $this->t('small'),
          18 => '18 pt',
          24 => '24 pt - ' . $this->t('normal'),
          30 => '30 pt',
          36 => '36 pt - ' . $this->t('large'),
          48 => '48 pt',
          64 => '64 pt - ' . $this->t('extra large'),
        ],
        '#default_value' => (int) $config->get('image_captcha_font_size'),
        '#description' => $this->t('The font size influences the size of the image. Note that larger values make the image generation more CPU intensive.'),
      ];
    }

    // Character spacing (available for both the TrueType
    // fonts and the builtin font.
    $form['image_captcha_font_settings']['image_captcha_character_spacing'] = [
      '#type' => 'select',
      '#title' => $this->t('Character spacing'),
      '#description' => $this->t('Define the average spacing between characters. Note that larger values make the image generation more CPU intensive.'),
      '#default_value' => $config->get('image_captcha_character_spacing'),
      '#options' => [
        '0.75' => $this->t('tight'),
        '1' => $this->t('normal'),
        '1.2' => $this->t('wide'),
        '1.5' => $this->t('extra wide'),
      ],
    ];

    return $form;
  }

  /**
   * Helper function to get fonts from the given directories.
   *
   * @param array|null $directories
   *   (Optional) an array of directories
   *   to recursively search through, if not given, the default
   *   directories will be used.
   *
   * @return array
   *   Fonts file objects (with fields 'name',
   *   'basename' and 'filename'), keyed on the sha256 hash of the font
   *   path (to have an easy token that can be used in an url
   *   without en/decoding issues).
   */
  protected function getAvailableFontsFromDirectories($directories = NULL) {
    // If no fonts directories are given: use the default.
    if ($directories === NULL) {
      $directories = [
        drupal_get_path('module', 'image_captcha') . '/fonts',
        'sites/all/libraries/fonts',
        DrupalKernel::findSitePath($this->getRequest()) . '/libraries/fonts',
      ];
    }
    // Collect the font information.
    $fonts = [];
    foreach ($directories as $directory) {
      foreach (file_scan_directory($directory, '/\.[tT][tT][fF]$/') as $filename => $font) {
        $fonts[hash('sha256', $filename)] = $font;
      }
    }

    return $fonts;
  }

}
