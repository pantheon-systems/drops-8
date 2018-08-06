<?php

namespace Drupal\webform\Plugin\WebformElement;

/**
 * Provides a 'webform_audio_file' element.
 *
 * @WebformElement(
 *   id = "webform_audio_file",
 *   label = @Translation("Audio file"),
 *   description = @Translation("Provides a form element for uploading and saving an audio file."),
 *   category = @Translation("File upload elements"),
 *   states_wrapper = TRUE,
 *   dependencies = {
 *     "file",
 *   }
 * )
 */
class WebformAudioFile extends WebformManagedFileBase {

  /**
   * {@inheritdoc}
   */
  public function getItemFormats() {
    $formats = parent::getItemFormats();
    $formats['file'] = $this->t('HTML5 Audio player (MP3 only)');
    return $formats;
  }

}
