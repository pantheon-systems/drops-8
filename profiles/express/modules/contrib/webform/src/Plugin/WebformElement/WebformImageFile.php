<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
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
  protected function defineDefaultProperties() {
    $properties = parent::defineDefaultProperties() + [
      'max_resolution' => '',
      'min_resolution' => '',
    ];
    if (\Drupal::moduleHandler()->moduleExists('image')) {
      $properties['attachment_image_style'] = '';
    }
    return $properties;
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    // Add upload resolution validation.
    $max_resolution = $this->getElementProperty($element, 'max_resolution');
    $min_resolution = $this->getElementProperty($element, 'min_resolution');
    if ($max_resolution || $min_resolution) {
      $element['#upload_validators']['file_validate_image_resolution'] = [$max_resolution, $min_resolution];
    }
  }

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
    $formats[$label][":image"] = $this->t('@label: Image', $t_args);
    $formats[$label][":link"] = $this->t('@label: Link', $t_args);
    $formats[$label][":modal"] = $this->t('@label: Modal', $t_args);
    if (\Drupal::moduleHandler()->moduleExists('image')) {
      $image_styles = $this->entityTypeManager->getStorage('image_style')->loadMultiple();
      foreach ($image_styles as $id => $image_style) {
        $label = (string) $image_style->label();
        $t_args = ['@label' => $label];
        $formats[$label]["$id:image"] = $this->t('@label: Image', $t_args);
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

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['image'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Image settings'),
    ];
    $form['image']['max_resolution'] = [
      '#type' => 'webform_image_resolution',
      '#title' => $this->t('Maximum image resolution'),
      '#description' => $this->t('The maximum allowed image size expressed as WIDTH×HEIGHT (e.g. 640×480). Leave blank for no restriction. If a larger image is uploaded, it will be resized to reflect the given width and height. Resizing images on upload will cause the loss of <a href="http://wikipedia.org/wiki/Exchangeable_image_file_format">EXIF data</a> in the image.'),
      '#width_title' => $this->t('Maximum width'),
      '#height_title' => $this->t('Maximum height'),
    ];
    $form['image']['min_resolution'] = [
      '#type' => 'webform_image_resolution',
      '#title' => $this->t('Minimum image resolution'),
      '#description' => $this->t('The minimum allowed image size expressed as WIDTH×HEIGHT (e.g. 640×480). Leave blank for no restriction. If a smaller image is uploaded, it will be rejected.'),
      '#width_title' => $this->t('Minimum width'),
      '#height_title' => $this->t('Minimum height'),
    ];
    if (\Drupal::moduleHandler()->moduleExists('image')) {
      $form['image']['attachment_image_style'] = [
        '#type' => 'select',
        '#options' => image_style_options(),
        '#title' => $this->t('Attachment image style'),
        '#description' => $this->t('Use this to send image with image style when sending files as attachment in an email handler.'),
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachments(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $attachments = [];

    /** @var \Drupal\image\ImageStyleInterface $image_style */
    $image_style = NULL;
    $attachment_image_style = $this->getElementProperty($element, 'attachment_image_style');
    if ($attachment_image_style && \Drupal::moduleHandler()->moduleExists('image')) {
      $image_style = $this->entityTypeManager
        ->getStorage('image_style')
        ->load($attachment_image_style);
    }

    $files = $this->getTargetEntities($element, $webform_submission, $options) ?: [];
    foreach ($files as $file) {
      if ($image_style) {
        $file_uri = $image_style->buildUri($file->getFileUri());
        if (!file_exists($file_uri)) {
          $image_style->createDerivative($file->getFileUri(), $file_uri);
        }
        $file_url = $image_style->buildUrl($file->getFileUri());
      }
      else {
        $file_uri = $file->getFileUri();
        $file_url = file_create_url($file->getFileUri());
      }
      $attachments[] = [
        'filecontent' => file_get_contents($file_uri),
        'filename' => $file->getFilename(),
        'filemime' => $file->getMimeType(),
        'filepath' => \Drupal::service('file_system')->realpath($file_uri),
        // URL is used when debugging or resending messages.
        // @see \Drupal\webform\Plugin\WebformHandler\EmailWebformHandler::buildAttachments
        '_fileurl' => $file_url,
      ];
    }
    return $attachments;
  }

}
