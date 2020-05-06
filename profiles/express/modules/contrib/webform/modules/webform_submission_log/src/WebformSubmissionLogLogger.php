<?php

namespace Drupal\webform_submission_log;

use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\Logger\RfcLoggerTrait;
use Psr\Log\LoggerInterface;

/**
 * Logger that listens for 'webform_submission' channel.
 */
class WebformSubmissionLogLogger implements LoggerInterface {

  use RfcLoggerTrait;

  /**
   * The webform submission log manager.
   *
   * @var \Drupal\webform_submission_log\WebformSubmissionLogManagerInterface
   */
  protected $logManager;

  /**
   * The message's placeholders parser.
   *
   * @var \Drupal\Core\Logger\LogMessageParserInterface
   */
  protected $parser;

  /**
   * WebformSubmissionLog constructor.
   *
   * @param \Drupal\Core\Logger\LogMessageParserInterface $parser
   *   The log message parser service.
   * @param \Drupal\webform_submission_log\WebformSubmissionLogManagerInterface $log_manager
   *   The webform submission log manager.
   */
  public function __construct(LogMessageParserInterface $parser, WebformSubmissionLogManagerInterface $log_manager) {
    $this->parser = $parser;
    $this->logManager = $log_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {
    // Only log the 'webform_submission' channel.
    if ($context['channel'] !== 'webform_submission') {
      return;
    }

    // Make sure the context contains a webform submission.
    if (!isset($context['webform_submission'])) {
      return;
    }

    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $context['webform_submission'];

    // Make sure webform submission log is enabled.
    if (!$webform_submission->getWebform()->hasSubmissionLog()) {
      return;
    }

    // Set default values.
    $context += [
      'handler_id' => '',
      'operation' => '',
      'data' => [],
    ];

    // Cast message to string.
    $message = (string) $message;
    $message_placeholders = $this->parser->parseMessagePlaceholders($message, $context);

    $this->logManager->insert([
      'webform_id' => $webform_submission->getWebform()->id(),
      'sid' => $webform_submission->id(),
      'handler_id' => $context['handler_id'],
      'operation' => $context['operation'],
      'uid' => $context['uid'],
      'message' => $message,
      'variables' => serialize($message_placeholders),
      'data' => serialize($context['data']),
      'timestamp' => $context['timestamp'],
    ]);
  }

}
