<?php

namespace Drupal\webform\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformAjaxElementTrait;
use Drupal\webform\Plugin\WebformHandlerMessageInterface;
use Drupal\webform\WebformRequestInterface;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a webform that resends webform submission.
 */
class WebformSubmissionResendForm extends FormBase {

  use WebformAjaxElementTrait;

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

    // Apply variants to the webform.
    $webform = $webform_submission->getWebform();
    $webform->applyVariants($webform_submission);

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
    $options = $this->getMessageHandlerOptions($webform_submission);

    // Get message handler id from form state or use the first message handler.
    if (!empty($form_state->getValue('message_handler_id'))) {
      $message_handler_id = $form_state->getValue('message_handler_id');
    }
    else {
      $message_handler_id = key($options);
    }

    // Display message handler with change message Ajax submit button.
    $form['message_handler'] = [];
    $form['message_handler']['message_handler_id'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#js_select' => TRUE,
      '#empty' => $this->t('No messages are available.'),
      '#multiple' => FALSE,
      '#default_value' => $message_handler_id,
    ];

    // Message.
    $message_handler = $this->webformSubmission->getWebform()->getHandler($message_handler_id);
    $message = $message_handler->getMessage($webform_submission);
    $resend_form = $message_handler->resendMessageForm($message);
    $form['message'] = [
      '#type' => 'details',
      '#title' => $this->t('Message'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ] + $resend_form;

    // Add resend button.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Resend message'),
    ];

    // Add submission navigation.
    $source_entity = $this->requestHandler->getCurrentSourceEntity('webform_submission');
    $form['navigation'] = [
      '#type' => 'webform_submission_navigation',
      '#webform_submission' => $webform_submission,
      '#weight' => -20,
    ];
    $form['information'] = [
      '#type' => 'webform_submission_information',
      '#webform_submission' => $webform_submission,
      '#source_entity' => $source_entity,
      '#weight' => -19,
    ];
    $form['#attached']['library'][] = 'webform/webform.admin';

    $this->buildAjaxElement(
      'webform-message-handler',
      $form['message'],
      $form['message_handler']['message_handler_id'],
      $form['message_handler']
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $params = $form_state->getValue('message');

    // Add webform submission.
    $params['webform_submission'] = $this->webformSubmission;

    $message_handler_id = $form_state->getValue('message_handler_id');
    $message_handler = $this->webformSubmission->getWebform()->getHandler($message_handler_id);

    $message_handler->sendMessage($this->webformSubmission, $params);

    $t_args = [
      '%label' => $message_handler->label(),
    ];
    $this->messenger()->addStatus($this->t('Successfully re-sent %label.', $t_args));
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

  /****************************************************************************/
  // Helper methods.
  /****************************************************************************/

  /**
   * Get a webform submission's message handlers as options.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return array
   *   An associative array containing a webform submission's message handlers
   *   as table select options.
   */
  protected function getMessageHandlerOptions(WebformSubmissionInterface $webform_submission) {
    $handlers = $webform_submission->getWebform()->getHandlers();

    // Get options.
    $options = [];
    foreach ($handlers as $handler_id => $message_handler) {
      if (!($message_handler instanceof WebformHandlerMessageInterface)) {
        continue;
      }

      $message = $message_handler->getMessage($webform_submission);

      $options[$handler_id]['title'] = [
        'data' => [
          'label' => [
            '#type' => 'label',
            '#title' => $message_handler->label() . ': ' . $message_handler->description(),
            '#title_display' => NULL,
            '#for' => 'edit-message-handler-id-' . str_replace('_', '-', $message_handler->getHandlerId()),
          ],
        ],
      ];
      $options[$handler_id]['id'] = [
        'data' => $message_handler->getHandlerId(),
      ];
      $options[$handler_id]['summary'] = [
        'data' => $message_handler->getMessageSummary($message),
      ];
      $options[$handler_id]['status'] = ($message_handler->isEnabled()) ? $this->t('Enabled') : $this->t('Disabled');
    }
    return $options;
  }

  /****************************************************************************/
  // Change message ajax callbacks.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public static function submitAjaxElementCallback(array $form, FormStateInterface $form_state) {
    // Unset the message so that it can be completely rebuilt.
    NestedArray::unsetValue($form_state->getUserInput(), ['message']);
    $form_state->unsetValue('message');

    // Rebuild the form.
    $form_state->setRebuild();
  }

}
