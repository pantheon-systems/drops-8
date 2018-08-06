<?php

namespace Drupal\webform\Element;

/**
 * Provides a webform element for an 'audio_file' element.
 *
 * @FormElement("webform_audio_file")
 */
class WebformAudioFile extends WebformManagedFileBase {

  /**
   * {@inheritdoc}
   */
  protected static $accept = 'audio/*';

}
