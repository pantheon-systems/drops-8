<?php

namespace Drupal\webform\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandler\EmailWebformHandler;
use Drupal\webform\WebformRequestInterface;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a webform that resends webform submission.
 */
class WebformSubmissionResendForm extends FormBase {

  /**
   * A webform submission.
   *
   * @var \Drupal\webform\WebformSubmissionInterface
   */
  protected $webformSubmission;

  /**
   * The source entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_submission_resend';
  }

  /**
   * Webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;

  /**
   * Constructs a WebformResultsResendForm object.
   *
   * @param \Drupal\webform\WebformRequestInterface $request_handler
   *   The webform request handler.
   */
  public function __construct(WebformRequestInterface $request_handler) {
    $this->requestHandler = $request_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('webform.request')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission = NULL) {
    $this->webformSubmission = $webform_submission;

    $handlers = $webform_submission->getWebform()->getHandlers();

    /** @var \Drupal\webform\Plugin\WebformHandlerMessageInterface[] $message_handlers */
    $message_handlers = [];
    foreach ($handlers as $handler_id => $handler) {
      if ($handler instanceof EmailWebformHandler) {
        $message_handlers[$handler_id] = $handler;
      }
    }

    // Get header.
    $header = [];
    $header['title'] = [
      'data' => $this->t('Title / Description'),
    ];
    $header['id'] = [
      'data' => $this->t('ID'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    $header['summary'] = [
      'data' => $this->t('summary'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    $header['status'] = [
      'data' => $this->t('Status'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];

    // Get options.
    $options = [];
    foreach ($message_handlers as $index => $message_handler) {
      $message = $message_handler->getMessage($this->webformSubmission);

      $options[$index]['title'] = [
        'data' => [
          'label' => [
            '#type' => 'label',
            '#title' => $message_handler->label() . ': ' . $message_handler->description(),
            '#title_display' => NULL,
            '#for' => 'edit-message-handler-id-' . str_replace('_', '-', $message_handler->getHandlerId()),
          ],
        ],
      ];
      $options[$index]['id'] = [
        'data' => $message_handler->getHandlerId(),
      ];
      $options[$index]['summary'] = [
        'data' => $message_handler->getMessageSummary($message),
      ];
      $options[$index]['status'] = ($message_handler->isEnabled()) ? $this->t('Enabled') : $this->t('Disabled');
    }

    // Get message handler id.
    if (empty($form_state->getValue('message_handler_id'))) {
      reset($options);
      $message_handler_id = key($options);
      $form_state->setValue('message_handler_id', $message_handler_id);
    }
    else {
      $message_handler_id = $form_state->getValue('message_handler_id');
    }

    $message_handler = $this->getMessageHandler($form_state);
    $form['message_handler_id'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#js_select' => TRUE,
      '#empty' => $this->t('No messages are available.'),
      '#multiple' => FALSE,
      '#default_value' => $message_handler_id,
      '#ajax' => [
        'callback' => '::updateMessage',
        'wrapper' => 'edit-webform-message-wrapper',
      ],
    ];

    // Message.
    $form['message'] = [
      '#type' => 'details',
      '#title' => $this->t('Message'),
      '#open' => TRUE,
      '#tree' => TRUE,
      '#prefix' => '<div id="edit-webform-message-wrapper">',
      '#suffix' => '</div>',
    ];
    $message = $message_handler->getMessage($webform_submission);
    $form['message'] += $message_handler->resendMessageForm($message);

    // Add resend button.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Resend message'),
    ];

    // Add submission navigation.
    $source_entity = $this->requestHandler->getCurrentSourceEntity('webform_submission');
    $form['navigation'] = [
      '#theme' => 'webform_submission_navigation',
      '#webform_submission' => $webform_submission,
      '#weight' => -20,
    ];
    $form['information'] = [
      '#theme' => 'webform_submission_information',
      '#webform_submission' => $webform_submission,
      '#source_entity' => $source_entity,
      '#weight' => -19,
    ];
    $form['#attached']['library'][] = 'webform/webform.admin';

    return $form;
  }

  /**
   * Handles switching between messages.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   An associative array containing an email message.
   */
  public function updateMessage(array $form, FormStateInterface $form_state) {
    $message_handler = $this->getMessageHandler($form_state);
    $message = $message_handler->getMessage($this->webformSubmission);
    foreach ($message as $key => $value) {
      $form['message'][$key]['#value'] = $value;
    }
    return $form['message'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $params = $form_state->getValue('message');
    $message_handler = $this->getMessageHandler($form_state);
    $message_handler->sendMessage($this->webformSubmission, $params);

    $t_args = [
      '%label' => $message_handler->label(),
    ];
    drupal_set_message($this->t('Successfully re-sent %label.', $t_args));
  }

  /**
   * Get message handler from webform state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\webform\Plugin\WebformHandlerMessageInterface
   *   The current message handler.
   */
  protected function getMessageHandler(FormStateInterface $form_state) {
    $message_handler_id = $form_state->getValue('message_handler_id');
    return $this->webformSubmission->getWebform()->getHandler($message_handler_id);
  }

}
