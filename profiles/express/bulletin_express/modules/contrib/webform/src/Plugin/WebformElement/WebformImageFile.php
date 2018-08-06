<?php

namespace Drupal\webform\Plugin\WebformElement;

/**
 * Provides a 'webform_image_file' element.
 *
 * @WebformElement(
 *   id = "webform_image_file",
 *   label = @Translation("Image file"),
 *   description = @Translation("Provides a form element for uploading and saving an image file."),
 *   category = @Translation("File upload elements"),
 *   states_wrapper = TRUE,
 *   dependencies = {
 *     "file",
 *   }
 * )
 */
class WebformImageFile extends WebformManagedFileBase {

  /**
   * {@inheritdoc}
   */
  public function getItemFormats() {
    $formats = parent::getItemFormats();
    $formats['file'] = $this->t('Image');
    return $formats;
  }

}
