<?php

namespace Drupal\colorbox;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * An implementation of PageAttachmentInterface for the colorbox library.
 */
class ColorboxAttachment implements ElementAttachmentInterface {

  use StringTranslationTrait;

  /**
   * The service to determin if colorbox should be activated.
   *
   * @var \Drupal\colorbox\ActivationCheckInterface
   */
  protected $activation;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The colorbox settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * Create an instance of ColorboxAttachment.
   */
  public function __construct(ActivationCheckInterface $activation, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config) {
    $this->activation = $activation;
    $this->moduleHandler = $module_handler;
    $this->settings = $config->get('colorbox.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable() {
    return !drupal_installation_attempted() && $this->activation->isActive();
  }

  /**
   * {@inheritdoc}
   */
  public function attach(array &$page) {
    if ($this->settings->get('custom.activate')) {
      $js_settings = array(
        'transition' => $this->settings->get('custom.transition_type'),
        'speed' => $this->settings->get('custom.transition_speed'),
        'opacity' => $this->settings->get('custom.opacity'),
        'slideshow' => $this->settings->get('custom.slideshow.slideshow') ? TRUE : FALSE,
        'slideshowAuto' => $this->settings->get('custom.slideshow.auto') ? TRUE : FALSE,
        'slideshowSpeed' => $this->settings->get('custom.slideshow.speed'),
        'slideshowStart' => $this->settings->get('custom.slideshow.text_start'),
        'slideshowStop' => $this->settings->get('custom.slideshow.text_stop'),
        'current' => $this->settings->get('custom.text_current'),
        'previous' => $this->settings->get('custom.text_previous'),
        'next' => $this->settings->get('custom.text_next'),
        'close' => $this->settings->get('custom.text_close'),
        'overlayClose' => $this->settings->get('custom.overlayclose') ? TRUE : FALSE,
        'returnFocus' => $this->settings->get('custom.returnfocus') ? TRUE : FALSE,
        'maxWidth' => $this->settings->get('custom.maxwidth'),
        'maxHeight' => $this->settings->get('custom.maxheight'),
        'initialWidth' => $this->settings->get('custom.initialwidth'),
        'initialHeight' => $this->settings->get('custom.initialheight'),
        'fixed' => $this->settings->get('custom.fixed') ? TRUE : FALSE,
        'scrolling' => $this->settings->get('custom.scrolling') ? TRUE : FALSE,
        'mobiledetect' => $this->settings->get('advanced.mobile_detect') ? TRUE : FALSE,
        'mobiledevicewidth' => $this->settings->get('advanced.mobile_device_width'),
      );
    }
    else {
      $js_settings = array(
        'opacity' => '0.85',
        'current' => $this->t('{current} of {total}'),
        'previous' => $this->t('« Prev'),
        'next' => $this->t('Next »'),
        'close' => $this->t('Close'),
        'maxWidth' => '98%',
        'maxHeight' => '98%',
        'fixed' => TRUE,
        'mobiledetect' => $this->settings->get('advanced.mobile_detect') ? TRUE : FALSE,
        'mobiledevicewidth' => $this->settings->get('advanced.mobile_device_width'),
      );
    }

    $style = $this->settings->get('custom.style');

    // Give other modules the possibility to override Colorbox
    // settings and style.
    $this->moduleHandler->alter('colorbox_settings', $js_settings, $style);

    // Add colorbox js settings.
    $page['#attached']['drupalSettings']['colorbox'] = $js_settings;

    // Add and initialise the Colorbox plugin.
    if ($this->settings->get('advanced.compression_type') == 'minified') {
      $page['#attached']['library'][] = 'colorbox/colorbox';
    }
    else {
      $page['#attached']['library'][] = 'colorbox/colorbox-dev';
    }

    // Add JS and CSS based on selected style.
    if ($style != 'none') {
      $page['#attached']['library'][] = "colorbox/$style";
    }
  }

}
