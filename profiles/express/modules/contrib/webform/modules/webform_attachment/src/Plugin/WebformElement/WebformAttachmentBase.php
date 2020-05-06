<?php

namespace Drupal\webform_attachment\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformMessage;
use Drupal\webform\Plugin\WebformElement\WebformDisplayOnTrait;
use Drupal\webform\Plugin\WebformElementAttachmentInterface;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\Plugin\WebformElementDisplayOnInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a base class for 'webform_attachment' elements.
 */
abstract class WebformAttachmentBase extends WebformElementBase implements WebformElementAttachmentInterface, WebformElementDisplayOnInterface {

  use WebformDisplayOnTrait;

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      // Element settings.
      'title' => '',
      // Form display.
      'title_display' => '',
      // Display settings.
      'display_on' => static::DISPLAY_ON_NONE,
      // Attachment values.
      'filename' => '',
      'sanitize' => FALSE,
      'link_title' => '',
      'trim' => FALSE,
      'download' => FALSE,
      // Attributes.
      'wrapper_attributes' => [],
      'label_attributes' => [],
    ] + $this->defineDefaultBaseProperties();
  }

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultBaseProperties() {
    $properties = parent::defineDefaultBaseProperties();
    unset(
      $properties['prepopulate'],
      $properties['states_clear']
    );
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineTranslatableProperties() {
    return array_merge(parent::defineTranslatableProperties(), ['filename', 'link_title']);
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    /** @var \Drupal\webform_attachment\Element\WebformAttachmentInterface $attachment_element */
    $attachment_element = $this->getFormElementClassDefinition();
    $format = $this->getItemFormat($element);
    switch ($format) {
      case 'name';
        return $attachment_element::getFileName($element, $webform_submission);

      case 'url';
        return $attachment_element::getFileUrl($element, $webform_submission)->toString();

      default:
      case 'link':
        return $attachment_element::getFileLink($element, $webform_submission);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    /** @var \Drupal\webform_attachment\Element\WebformAttachmentInterface $attachment_element */
    $attachment_element = $this->getFormElementClassDefinition();

    $format = $this->getItemFormat($element);
    switch ($format) {
      case 'name';
        return $attachment_element::getFileName($element, $webform_submission);

      default:
      case 'link';
      case 'url';
        return $attachment_element::getFileUrl($element, $webform_submission)->toString();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormats() {
    return [
      'link' => $this->t('File link'),
      'name' => $this->t('File name'),
      'url' => $this->t('File URL'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefaultFormat() {
    return 'link';
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(array &$element, WebformSubmissionInterface $webform_submission) {
    $key = $element['#webform_key'];
    $data = $webform_submission->getData();
    // Make sure attachment element never stores a value.
    if (isset($data[$key])) {
      unset($data[$key]);
      $webform_submission->setData($data);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['attachment'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Attachment settings'),
    ];
    $form['attachment']['message'] = [
      '#type' => 'webform_message',
      '#message_message' => $this->t("Please make sure to enable 'Include files as attachments' for each email handler that you want to send this attachment."),
      '#message_type' => 'warning',
      '#message_close' => TRUE,
      '#message_storage' => WebformMessage::STORAGE_SESSION,
      '#access' => TRUE,
    ];
    $form['attachment']['display_on'] = [
      '#type' => 'select',
      '#title' => $this->t('Display on'),
      '#options' => $this->getDisplayOnOptions(TRUE),
    ];
    $form['attachment']['display_on_message'] = [
      '#type' => 'webform_message',
      '#message_message' => $this->t("The attachment's link will only be diplayed on the form after the submission is completed."),
      '#message_type' => 'warning',
      '#access' => TRUE,
      '#states' => [
        'visible' => [
          [':input[name="properties[display_on]"]' => ['value' => 'form']],
          'or',
          [':input[name="properties[display_on]"]' => ['value' => 'both']],
        ],
      ],
    ];
    $form['attachment']['filename'] = [
      '#type' => 'textfield',
      '#title' => $this->t('File name'),
      '#description' => $this->t("Please enter the attachment's file name with a file extension. The file extension will be used to determine the attachment's content (mime) type."),
      '#maxlength' => NULL,
    ];
    $form['attachment']['link_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link title'),
      '#description' => $this->t('Enter the title to be displayed when the attachment is displayed as a link.'),
    ];
    $form['attachment']['trim'] = [
      '#type' => 'checkbox',
      '#return_value' => TRUE,
      '#title' => $this->t("Remove whitespace around the attachment's content"),
      '#description' => $this->t("If checked, all spaces and returns around the attachment's content with be removed."),
      '#weight' => 10,
    ];
    $form['attachment']['sanitize'] = [
      '#type' => 'checkbox',
      '#return_value' => TRUE,
      '#title' => $this->t('Sanitize file name'),
      '#description' => $this->t('If checked, file name will be transliterated, lower-cased and all special characters converted to dashes (-).'),
      '#weight' => 10,
    ];
    $form['attachment']['download'] = [
      '#type' => 'checkbox',
      '#return_value' => TRUE,
      '#title' => $this->t('Force users to download the attachment'),
      '#description' => $this->t('If checked the attachment will be automatically download.'),
      '#weight' => 10,
    ];
    $form['attachment']['tokens'] = ['#access' => TRUE, '#weight' => 10] + $this->tokenManager->buildTreeElement();

    // Add warning about disabled attachments.
    $form['conditional_logic']['states_attachment'] = [
      '#type' => 'webform_message',
      '#message_message' => t('Disabled attachments will not be included as file attachments in sent emails.'),
      '#message_type' => 'warning',
      '#message_close' => TRUE,
      '#message_storage' => WebformMessage::STORAGE_SESSION,
      '#access' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    // Attachment elements should never get a test value.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachments(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    /** @var \Drupal\webform\WebformSubmissionConditionsValidatorInterface $conditions_validator */
    $conditions_validator = \Drupal::service('webform_submission.conditions_validator');
    if (!$conditions_validator->isElementEnabled($element, $webform_submission)) {
      return [];
    }

    /** @var \Drupal\webform_attachment\Element\WebformAttachmentInterface $attachment_element */
    $attachment_element = $this->getFormElementClassDefinition();

    $file_content = $attachment_element::getFileContent($element, $webform_submission);
    $file_name = $attachment_element::getFileName($element, $webform_submission);
    $file_mime = $attachment_element::getFileMimeType($element, $webform_submission);
    $file_url = $attachment_element::getFileUrl($element, $webform_submission);

    $attachments = [];
    if ($file_name && $file_content && $file_mime) {
      $attachments[] = [
        'filecontent' => $file_content,
        'filename' => $file_name,
        'filemime' => $file_mime,
        // URI is used when debugging or resending messages.
        // @see \Drupal\webform\Plugin\WebformHandler\EmailWebformHandler::buildAttachments
        '_fileurl' => ($file_url) ? $file_url->toString() : NULL,
      ];
    }
    return $attachments;
  }

}
