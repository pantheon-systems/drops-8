<?php

namespace Drupal\webform\Element;

use Drupal\Core\Render\Element\FormElement;
use Drupal\file\Element\ManagedFile;

// As we do not force dependency on the core file module, we do this If
// statement. So if File module is enabled, we use it, otherwise we fallback on
// useless dummy implementation just to keep PHP interpreter happy about
// inheriting an existing class.
if (class_exists('\Drupal\file\Element\ManagedFile')) {

  /**
   * Provides a base class for 'managed_file' elements.
   */
  abstract class WebformManagedFileBase extends ManagedFile {

    /**
     * The types of files that the server accepts.
     *
     * @var string
     *
     * @see http://www.w3schools.com/tags/att_input_accept.asp
     */
    protected static $accept;

    /**
     * {@inheritdoc}
     */
    public function getInfo() {
      $info = parent::getInfo();
      $info['#pre_render'][] = [get_class($this), 'preRenderWebformManagedFile'];
      return $info;
    }

    /**
     * Render API callback: Adds media capture to the managed_file element type.
     */
    public static function preRenderWebformManagedFile($element) {
      // Set accept and capture attributes.
      if (isset($element['upload']) && static::$accept) {
        $element['upload']['#attributes']['accept'] = static::$accept;;
      }

      // Add class name to wrapper attributes.
      $class_name = str_replace('_', '-', $element['#type']);
      static::setAttributes($element, ['js-' . $class_name, $class_name]);

      return $element;
    }

  }

}
else {

  /**
   * Provides a empty base class for 'managed_file' elements.
   */
  abstract class WebformManagedFileBase extends FormElement {

    /**
     * {@inheritdoc}
     */
    public function getInfo() {
      return [];
    }

  }

}
