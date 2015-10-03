<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\Fieldset.
 */

namespace Drupal\Core\Render\Element;

use Drupal\Core\Render\Element;

/**
 * Provides a render element for a group of form elements.
 *
 * Usage example:
 * @code
 * $form['author'] = array(
 *   '#type' => 'fieldset',
 *   '#title' => 'Author',
 * );
 *
 * $form['author']['name'] = array(
 *   '#type' => 'textfield',
 *   '#title' => t('Name'),
 * );
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Fieldgroup
 * @see \Drupal\Core\Render\Element\Details
 *
 * @RenderElement("fieldset")
 */
class Fieldset extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#process' => array(
        array($class, 'processGroup'),
        array($class, 'processAjaxForm'),
      ),
      '#pre_render' => array(
        array($class, 'preRenderGroup'),
      ),
      '#value' => NULL,
      '#theme_wrappers' => array('fieldset'),
    );
  }

}
