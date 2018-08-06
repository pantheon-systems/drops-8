<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\webform\WebformSubmissionInterface;

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
  public function getItemDefaultFormat() {
    return ':image';
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormats() {
    $formats = parent::getItemFormats();

    // Instead of 'file' the item default format is ':image'.
    unset($formats['file']);

    // Add support :image, :link, and :modal.
    $label = (string) $this->t('Original Image');
    $t_args = ['@label' => $label];
    $formats[$label][":image"] = $this->t('@label: Image', $t_args);;
    $formats[$label][":link"] = $this->t('@label: Link', $t_args);
    $formats[$label][":modal"] = $this->t('@label: Modal', $t_args);
    if (\Drupal::moduleHandler()->moduleExists('image')) {
      $image_styles = $this->entityTypeManager->getStorage('image_style')->loadMultiple();
      foreach ($image_styles as $id => $image_style) {
        $label = (string) $image_style->label();
        $t_args = ['@label' => $label];
        $formats[$label]["$id:image"] = $this->t('@label: Image', $t_args);;
        $formats[$label]["$id:link"] = $this->t('@label: Link', $t_args);
        $formats[$label]["$id:modal"] = $this->t('@label: Modal', $t_args);
      }
    }
    return $formats;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);
    $file = $this->getFile($element, $value, $options);
    $format = $this->getItemFormat($element);
    if (strpos($format, ':') === FALSE) {
      return parent::formatHtmlItem($element, $webform_submission, $options);
    }
    else {
      list($style_name, $format) = explode(':', $format);
      $theme = str_replace('webform_', 'webform_element_', $this->getPluginId());
      if (strpos($theme, 'webform_') !== 0) {
        $theme = 'webform_element_' . $theme;
      }
      return [
        '#theme' => $theme,
        '#element' => $element,
        '#value' => $value,
        '#options' => $options,
        '#file' => $file,
        '#style_name' => $style_name,
        '#format' => $format,
      ];
    }
  }

}
