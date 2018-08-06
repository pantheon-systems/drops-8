<?php

namespace Drupal\colorbox\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * General configuration form for controlling the colorbox behaviour..
 */
class ColorboxSettingsForm extends ConfigFormBase {

  /**
   * A state that represents the custom settings being enabled.
   */
  const STATE_CUSTOM_SETTINGS = 0;

  /**
   * A state that represents the slideshow being enabled.
   */
  const STATE_SLIDESHOW_ENABLED = 1;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'colorbox_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['colorbox.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->configFactory->get('colorbox.settings');

    $form['colorbox_custom_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Styles and options'),
      '#open' => TRUE,
    ];
    $colorbox_styles = [
      'default' => $this->t('Default'),
      'plain' => $this->t('Plain (mainly for images)'),
      'stockholmsyndrome' => $this->t('Stockholm Syndrome'),
      'example1' => $this->t('Example 1'),
      'example2' => $this->t('Example 2'),
      'example3' => $this->t('Example 3'),
      'example4' => $this->t('Example 4'),
      'example5' => $this->t('Example 5'),
      'none' => $this->t('None'),
    ];
    $form['colorbox_custom_settings']['colorbox_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Style'),
      '#options' => $colorbox_styles,
      '#default_value' => $config->get('custom.style'),
      '#description' => $this->t('Select the style to use for the Colorbox. The example styles are the ones that come with the Colorbox plugin. Select "None" if you have added Colorbox styles to your theme.'),
    ];
    $form['colorbox_custom_settings']['colorbox_custom_settings_activate'] = [
      '#type' => 'radios',
      '#title' => $this->t('Options'),
      '#options' => [0 => $this->t('Default'), 1 => $this->t('Custom')],
      '#default_value' => $config->get('custom.activate'),
      '#description' => $this->t('Use the default or custom options for Colorbox.'),
    ];
    $form['colorbox_custom_settings']['colorbox_transition_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Transition type'),
      '#options' => [
        'elastic' => $this->t('Elastic'),
        'fade' => $this->t('Fade'),
        'none' => $this->t('None'),
      ],
      '#default_value' => $config->get('custom.transition_type'),
      '#description' => $this->t('The transition type.'),
      '#states' => $this->getState(static::STATE_CUSTOM_SETTINGS),
    ];
    $form['colorbox_custom_settings']['colorbox_transition_speed'] = [
      '#type' => 'select',
      '#title' => $this->t('Transition speed'),
      '#options' => $this->optionsRange(100, 600, 50),
      '#default_value' => $config->get('custom.transition_speed'),
      '#description' => $this->t('Sets the speed of the fade and elastic transitions, in milliseconds.'),
      '#states' => $this->getState(static::STATE_CUSTOM_SETTINGS),
    ];
    $form['colorbox_custom_settings']['colorbox_opacity'] = [
      '#type' => 'select',
      '#title' => $this->t('Opacity'),
      '#options' => $this->optionsRange(0, 1, 0.05),
      '#default_value' => $config->get('custom.opacity'),
      '#description' => $this->t('The overlay opacity level. Range: 0 to 1.'),
      '#states' => $this->getState(static::STATE_CUSTOM_SETTINGS),
    ];
    $form['colorbox_custom_settings']['colorbox_text_current'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Current'),
      '#default_value' => $config->get('custom.text_current'),
      '#size' => 30,
      '#description' => $this->t('Text format for the content group / gallery count. {current} and {total} are detected and replaced with actual numbers while Colorbox runs.'),
      '#states' => $this->getState(static::STATE_CUSTOM_SETTINGS),
    ];
    $form['colorbox_custom_settings']['colorbox_text_previous'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Previous'),
      '#default_value' => $config->get('custom.text_previous'),
      '#size' => 30,
      '#description' => $this->t('Text for the previous button in a shared relation group.'),
      '#states' => $this->getState(static::STATE_CUSTOM_SETTINGS),
    ];
    $form['colorbox_custom_settings']['colorbox_text_next'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Next'),
      '#default_value' => $config->get('custom.text_next'),
      '#size' => 30,
      '#description' => $this->t('Text for the next button in a shared relation group.'),
      '#states' => $this->getState(static::STATE_CUSTOM_SETTINGS),
    ];
    $form['colorbox_custom_settings']['colorbox_text_close'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Close'),
      '#default_value' => $config->get('custom.text_close'),
      '#size' => 30,
      '#description' => $this->t('Text for the close button. The "Esc" key will also close Colorbox.'),
      '#states' => $this->getState(static::STATE_CUSTOM_SETTINGS),
    ];
    $form['colorbox_custom_settings']['colorbox_maxwidth'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Max width'),
      '#default_value' => $config->get('custom.maxwidth'),
      '#size' => 30,
      '#description' => $this->t('Set a maximum width for loaded content. Example: "100%", 500, "500px".'),
      '#states' => $this->getState(static::STATE_CUSTOM_SETTINGS),
    ];
    $form['colorbox_custom_settings']['colorbox_maxheight'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Max height'),
      '#default_value' => $config->get('custom.maxheight'),
      '#size' => 30,
      '#description' => $this->t('Set a maximum height for loaded content. Example: "100%", 500, "500px".'),
      '#states' => $this->getState(static::STATE_CUSTOM_SETTINGS),
    ];
    $form['colorbox_custom_settings']['colorbox_initialwidth'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Initial width'),
      '#default_value' => $config->get('custom.initialwidth'),
      '#size' => 30,
      '#description' => $this->t('Set the initial width, prior to any content being loaded. Example: "100%", 500, "500px".'),
      '#states' => $this->getState(static::STATE_CUSTOM_SETTINGS),
    ];
    $form['colorbox_custom_settings']['colorbox_initialheight'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Initial height'),
      '#default_value' => $config->get('custom.initialheight'),
      '#size' => 30,
      '#description' => $this->t('Set the initial height, prior to any content being loaded. Example: "100%", 500, "500px".'),
      '#states' => $this->getState(static::STATE_CUSTOM_SETTINGS),
    ];
    $form['colorbox_custom_settings']['colorbox_overlayclose'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Overlay close'),
      '#default_value' => $config->get('custom.overlayclose'),
      '#description' => $this->t('Enable closing Colorbox by clicking on the background overlay.'),
      '#states' => $this->getState(static::STATE_CUSTOM_SETTINGS),
    ];
    $form['colorbox_custom_settings']['colorbox_returnfocus'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Return focus'),
      '#default_value' => $config->get('custom.returnfocus'),
      '#description' => $this->t('Return focus when Colorbox exits to the element it was launched from.'),
      '#states' => $this->getState(static::STATE_CUSTOM_SETTINGS),
    ];
    $form['colorbox_custom_settings']['colorbox_fixed'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Fixed'),
      '#default_value' => $config->get('custom.fixed'),
      '#description' => $this->t("If the Colorbox should be displayed in a fixed position within the visitor's viewport or relative to the document."),
      '#states' => $this->getState(static::STATE_CUSTOM_SETTINGS),
    ];
    $form['colorbox_custom_settings']['colorbox_scrolling'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Scrollbars'),
      '#default_value' => $config->get('custom.scrolling'),
      '#description' => $this->t('If unchecked, Colorbox will hide scrollbars for overflowing content. This could be used on conjunction with the resize method for a smoother transition if you are appending content to an already open instance of Colorbox.'),
      '#states' => $this->getState(static::STATE_CUSTOM_SETTINGS),
    ];

    $form['colorbox_custom_settings']['colorbox_slideshow_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Slideshow settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#states' => $this->getState(static::STATE_CUSTOM_SETTINGS),
    ];
    $form['colorbox_custom_settings']['colorbox_slideshow_settings']['colorbox_slideshow'] = [
      '#type' => 'radios',
      '#title' => $this->t('Slideshow'),
      '#options' => [0 => $this->t('Off'), 1 => $this->t('On')],
      '#default_value' => $config->get('custom.slideshow.slideshow'),
      '#description' => $this->t('An automatic slideshow to a content group / gallery.'),
    ];
    $form['colorbox_custom_settings']['colorbox_slideshow_settings']['colorbox_slideshowauto'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Slideshow autostart'),
      '#default_value' => $config->get('custom.slideshow.auto'),
      '#description' => $this->t('If the slideshow should automatically start to play.'),
      '#states' => $this->getState(static::STATE_SLIDESHOW_ENABLED),
    ];
    $form['colorbox_custom_settings']['colorbox_slideshow_settings']['colorbox_slideshowspeed'] = [
      '#type' => 'select',
      '#title' => $this->t('Slideshow speed'),
      '#options' => $this->optionsRange(1000, 6000, 500),
      '#default_value' => $config->get('custom.slideshow.speed'),
      '#description' => $this->t('Sets the speed of the slideshow, in milliseconds.'),
      '#states' => $this->getState(static::STATE_SLIDESHOW_ENABLED),
    ];
    $form['colorbox_custom_settings']['colorbox_slideshow_settings']['colorbox_text_start'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Start slideshow'),
      '#default_value' => $config->get('custom.slideshow.text_start'),
      '#size' => 30,
      '#description' => $this->t('Text for the slideshow start button.'),
      '#states' => $this->getState(static::STATE_SLIDESHOW_ENABLED),
    ];
    $form['colorbox_custom_settings']['colorbox_slideshow_settings']['colorbox_text_stop'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Stop slideshow'),
      '#default_value' => $config->get('custom.slideshow.text_stop'),
      '#size' => 30,
      '#description' => $this->t('Text for the slideshow stop button.'),
      '#states' => $this->getState(static::STATE_SLIDESHOW_ENABLED),
    ];

    $form['colorbox_advanced_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
    ];
    $form['colorbox_advanced_settings']['colorbox_unique_token'] = [
      '#type' => 'radios',
      '#title' => $this->t('Unique per-request gallery token'),
      '#options' => [1 => $this->t('On'), 0 => $this->t('Off')],
      '#default_value' => $config->get('advanced.unique_token'),
      '#description' => $this->t('If On (default), Colorbox will add a unique per-request token to the gallery id to avoid images being added manually to galleries. The token was added as a security fix but some see the old behavoiur as an feature and this settings makes it possible to remove the token.'),
    ];
    $form['colorbox_advanced_settings']['colorbox_mobile_detect'] = [
      '#type' => 'radios',
      '#title' => $this->t('Mobile detection'),
      '#options' => [1 => $this->t('On'), 0 => $this->t('Off')],
      '#default_value' => $config->get('advanced.mobile_detect'),
      '#description' => $this->t('If on (default) Colorbox will not be active for devices with the max width set below.'),
    ];
    $form['colorbox_advanced_settings']['colorbox_mobile_device_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Device width'),
      '#default_value' => $config->get('advanced.mobile_device_width'),
      '#size' => 30,
      '#description' => $this->t('Set the mobile device max width. Default: 480px.'),
      '#states' => [
        'visible' => [
          ':input[name="colorbox_mobile_detect"]' => ['value' => '1'],
        ],
      ],
    ];
    $form['colorbox_advanced_settings']['colorbox_caption_trim'] = [
      '#type' => 'radios',
      '#title' => $this->t('Caption shortening'),
      '#options' => [0 => $this->t('Default'), 1 => $this->t('Yes')],
      '#default_value' => $config->get('advanced.caption_trim'),
      '#description' => $this->t('If the caption should be made shorter in the Colorbox to avoid layout problems. The default is to shorten for the example styles, they need it, but not for other styles.'),
    ];
    $form['colorbox_advanced_settings']['colorbox_caption_trim_length'] = [
      '#type' => 'select',
      '#title' => $this->t('Caption max length'),
      '#options' => $this->optionsRange(40, 120, 5),
      '#default_value' => $config->get('advanced.caption_trim_length'),
      '#states' => [
        'visible' => [
          ':input[name="colorbox_caption_trim"]' => ['value' => '1'],
        ],
      ],
    ];
    $form['colorbox_advanced_settings']['colorbox_compression_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Choose Colorbox compression level'),
      '#options' => [
        'minified' => $this->t('Production (Minified)'),
        'source' => $this->t('Development (Uncompressed Code)'),
      ],
      '#default_value' => $config->get('advanced.compression_type'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->configFactory->getEditable('colorbox.settings');

    $config
      ->set('custom.style', $form_state->getValue('colorbox_style'))
      ->set('custom.activate', $form_state->getValue('colorbox_custom_settings_activate'))
      ->set('custom.transition_type', $form_state->getValue('colorbox_transition_type'))
      ->set('custom.transition_speed', $form_state->getValue('colorbox_transition_speed'))
      ->set('custom.opacity', $form_state->getValue('colorbox_opacity'))
      ->set('custom.text_current', $form_state->getValue('colorbox_text_current'))
      ->set('custom.text_previous', $form_state->getValue('colorbox_text_previous'))
      ->set('custom.text_next', $form_state->getValue('colorbox_text_next'))
      ->set('custom.text_close', $form_state->getValue('colorbox_text_close'))
      ->set('custom.overlayclose', $form_state->getValue('colorbox_overlayclose'))
      ->set('custom.returnfocus', $form_state->getValue('colorbox_returnfocus'))
      ->set('custom.maxwidth', $form_state->getValue('colorbox_maxwidth'))
      ->set('custom.maxheight', $form_state->getValue('colorbox_maxheight'))
      ->set('custom.initialwidth', $form_state->getValue('colorbox_initialwidth'))
      ->set('custom.initialheight', $form_state->getValue('colorbox_initialheight'))
      ->set('custom.fixed', $form_state->getValue('colorbox_fixed'))
      ->set('custom.scrolling', $form_state->getValue('colorbox_scrolling'))
      ->set('custom.slideshow.slideshow', $form_state->getValue('colorbox_slideshow'))
      ->set('custom.slideshow.auto', $form_state->getValue('colorbox_slideshowauto'))
      ->set('custom.slideshow.speed', $form_state->getValue('colorbox_slideshowspeed'))
      ->set('custom.slideshow.text_start', $form_state->getValue('colorbox_text_start'))
      ->set('custom.slideshow.text_stop', $form_state->getValue('colorbox_text_stop'))
      ->set('advanced.unique_token', $form_state->getValue('colorbox_unique_token'))
      ->set('advanced.mobile_detect', $form_state->getValue('colorbox_mobile_detect'))
      ->set('advanced.mobile_device_width', $form_state->getValue('colorbox_mobile_device_width'))
      ->set('advanced.caption_trim', $form_state->getValue('colorbox_caption_trim'))
      ->set('advanced.caption_trim_length', $form_state->getValue('colorbox_caption_trim_length'))
      ->set('advanced.compression_type', $form_state->getValue('colorbox_compression_type'));

    if ($form_state->getValue('colorbox_image_style')) {
      $config->set('insert.image_style', $form_state->getValue('colorbox_image_style'));
    }

    if ($form_state->getValue('colorbox_insert_gallery')) {
      $config->set('insert.insert_gallery', $form_state->getValue('colorbox_insert_gallery'));
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Get one of the pre-defined states used in this form.
   *
   * @param string $state
   *   The state to get that matches one of the state class constants.
   *
   * @return array
   *   A corresponding form API state.
   */
  protected function getState($state) {
    $states = [
      static::STATE_CUSTOM_SETTINGS => [
        'visible' => [
          ':input[name="colorbox_custom_settings_activate"]' => ['value' => '1'],
        ],
      ],
      static::STATE_SLIDESHOW_ENABLED => [
        'visible' => [
          ':input[name="colorbox_slideshow"]' => ['value' => '1'],
        ],
      ],
    ];
    return $states[$state];
  }

  /**
   * Create a range for a series of options.
   *
   * @param int $start
   *   The start of the range.
   * @param int $end
   *   The end of the range.
   * @param int $step
   *   The interval between elements.
   *
   * @return array
   *   An options array for the given range.
   */
  protected function optionsRange($start, $end, $step) {
    $range = range($start, $end, $step);
    return array_combine($range, $range);
  }

}
