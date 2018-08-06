<?php

namespace Drupal\webform\Plugin\WebformElement;

/**
 * Provides a 'webform_video_file' element.
 *
 * @WebformElement(
 *   id = "webform_video_file",
 *   label = @Translation("Video file"),
 *   description = @Translation("Provides a form element for uploading and saving a video file."),
 *   category = @Translation("File upload elements"),
 *   states_wrapper = TRUE,
 *   dependencies = {
 *     "file",
 *   }
 * )
 */
class WebformVideoFile extends WebformManagedFileBase {

  /**
   * {@inheritdoc}
   */
  public function getItemFormats() {
    $formats = parent::getItemFormats();
    $formats['file'] = $this->t('HTML5 Video player (MP4 only)');
    return $formats;
  }

}
