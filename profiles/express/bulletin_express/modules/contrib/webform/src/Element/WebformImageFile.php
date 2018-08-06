<?php

namespace Drupal\webform\Element;

/**
 * Provides a webform element for an 'image_file' element.
 *
 * @FormElement("webform_image_file")
 */
class WebformImageFile extends WebformManagedFileBase {

  /**
   * {@inheritdoc}
   */
  protected static $accept = 'image/*';

}
