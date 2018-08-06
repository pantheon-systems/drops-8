<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformHtmlEditor;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\Element\WebformMessage as WebformMessageElement;

/**
 * Provides a 'webform_message' element.
 *
 * @WebformElement(
 *   id = "webform_message",
 *   label = @Translation("Message"),
 *   description = @Translation("Provides an element to render custom, dismissible, inline status messages."),
 *   category = @Translation("Markup elements"),
 * )
 */
class WebformMessage extends WebformMarkupBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return [
      // Attributes.
      'attributes' => [],
      // Message settings.
      'message_type' => 'status',
      'message_message' => '',
      'message_close' => FALSE,
      'message_close_effect' => 'slide',
      'message_id' => '',
      'message_storage' => '',
    ] + parent::getDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatableProperties() {
    return array_merge(parent::getTranslatableProperties(), ['message_message']);
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    if (!empty($element['#message_storage']) && empty($element['#message_id'])) {
      // Use
      // [webform:id]--[source_entity:type]-[source_entity:id]--[element:key]
      // as the message id.
      $id = [];
      if ($webform = $webform_submission->getWebform()) {
        $id[] = $webform->id();
      }
      if ($source_entity = $webform_submission->getSourceEntity()) {
        $id[] = $source_entity->getEntityTypeId() . '-' . $source_entity->id();
      }
      $id[] = $element['#webform_key'];
      $element['#message_id'] = implode('--', $id);
    }

    if (isset($element['#message_message'])) {
      $element['#message_message'] = WebformHtmlEditor::checkMarkup($element['#message_message']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return parent::preview() + [
      '#message_type' => 'warning',
      '#message_message' => $this->t('This is a <strong>warning</strong> message.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['markup']['#title'] = $this->t('Message settings');
    $form['markup']['message_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Message type'),
      '#options' => [
        'status' => t('Status'),
        'error' => t('Error'),
        'warning' => t('Warning'),
        'info' => t('Info'),
      ],
      '#required' => TRUE,
    ];
    $form['markup']['message_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Message content'),
      '#required' => TRUE,
    ];
    $form['markup']['message_close'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to close the message'),
      '#return_value' => TRUE,
    ];
    $form['markup']['message_close_effect'] = [
      '#type' => 'select',
      '#title' => $this->t('Message close effect'),
      '#options' => [
        'hide' => $this->t('Hide'),
        'slide' => $this->t('Slide'),
        'fade' => $this->t('Fade'),
      ],
      '#states' => [
        'visible' => [':input[name="properties[message_close]"]' => ['checked' => TRUE]],
      ],
    ];
    $form['markup']['message_storage'] = [
      '#type' => 'radios',
      '#title' => $this->t('Message storage'),
      '#options' => [
        WebformMessageElement::STORAGE_NONE => $this->t('None -- Message state is never stored.'),
        WebformMessageElement::STORAGE_SESSION => $this->t('Session storage -- Message state is reset after the browser is closed.'),
        WebformMessageElement::STORAGE_LOCAL => $this->t('Local storage -- Message state persists after the browser is closed.'),
        WebformMessageElement::STORAGE_USER => $this->t("User data -- Message state is saved to the current user's data. (Applies to authenticated users only)"),
        WebformMessageElement::STORAGE_STATE => $this->t("State API -- Message state is saved to the site's system state. (Applies to authenticated users only)"),
      ],
      '#options_description_display' => 'help',
      '#states' => [
        'visible' => [':input[name="properties[message_close]"]' => ['checked' => TRUE]],
      ],
    ];
    $form['markup']['message_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message ID'),
      '#description' => $this->t("Unique ID used to store the message's closed state. Please enter only lower-case letters, numbers, dashes, and underscores.") . '<br /><br />' .
      $this->t('Defaults to: %value', ['%value' => '[webform:id]--[element:key]']),
      '#pattern' => '/^[a-z0-9-_]+$/',
      '#states' => [
        'visible' => [':input[name="properties[message_close]"]' => ['checked' => TRUE]],
      ],
    ];
    return $form;
  }

}
