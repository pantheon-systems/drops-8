<?php

namespace Drupal\webform;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webform\Element\WebformHtmlEditor;
use Psr\Log\LoggerInterface;

/**
 * Defines the webform message (and login) manager.
 */
class WebformMessageManager implements WebformMessageManagerInterface {

  use StringTranslationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Webform submission storage.
   *
   * @var \Drupal\webform\WebformSubmissionStorageInterface
   */
  protected $entityStorage;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;

  /**
   * The webform token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * A webform.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;

  /**
   * The source entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $sourceEntity;

  /**
   * A webform submission.
   *
   * @var \Drupal\webform\WebformSubmissionInterface
   */
  protected $webformSubmission;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a WebformMessageManager object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\webform\WebformRequestInterface $request_handler
   *   The webform request handler.
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The webform token manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(AccountInterface $current_user, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger, RendererInterface $renderer, MessengerInterface $messenger, WebformRequestInterface $request_handler, WebformTokenManagerInterface $token_manager) {
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
    $this->entityStorage = $entity_type_manager->getStorage('webform_submission');
    $this->logger = $logger;
    $this->renderer = $renderer;
    $this->messenger = $messenger;
    $this->requestHandler = $request_handler;
    $this->tokenManager = $token_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function setWebformSubmission(WebformSubmissionInterface $webform_submission = NULL) {
    $this->webformSubmission = $webform_submission;
    if ($webform_submission) {
      $this->webform = $webform_submission->getWebform();
      $this->sourceEntity = $webform_submission->getSourceEntity();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setWebform(WebformInterface $webform = NULL) {
    $this->webform = $webform;
  }

  /**
   * {@inheritdoc}
   */
  public function setSourceEntity(EntityInterface $entity = NULL) {
    $this->sourceEntity = $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function append(array $build, $key, $type = 'status') {
    $message = $this->build($key);
    if ($message) {
      // Append namespace message and allow for multiple messages.
      $build['webform_message'][] = [
        '#type' => 'webform_message',
        '#message_message' => $message,
        '#message_type' => $type,
        '#weight' => -100,
      ];
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function display($key, $type = 'status') {
    if ($build = $this->build($key)) {
      $this->messenger->addMessage($this->renderer->renderPlain($build), $type);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render($key) {
    $build = $this->build($key);
    return ($build) ? $this->renderer->renderPlain($build) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function build($key) {
    if ($message = $this->get($key)) {
      // Make sure $message is renderable array.
      if (!is_array($message)) {
        $message = [
          '#markup' => $message,
          '#allowed_tags' => Xss::getAdminTagList(),
        ];
      }

      // Set max-age to 0 if settings message contains any [token] values.
      $setting_message = $this->getSetting($key);
      if ($setting_message && strpos($setting_message, '[') !== FALSE) {
        $message['#cache']['max-age'] = 0;
      }

      return $message;
    }
    else {
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get($key) {
    // Get custom message from settings.
    if ($custom_message = $this->getCustomMessage($key)) {
      return $custom_message;
    }

    $webform = $this->webform;
    $source_entity = $this->sourceEntity;

    // Get custom messages with :href argument.
    switch ($key) {
      case WebformMessageManagerInterface::DRAFT_PENDING_SINGLE:
        $webform_draft = $this->entityStorage->loadDraft($webform, $source_entity, $this->currentUser);
        $args = [':href' => $webform_draft->getTokenUrl()->toString()];
        return $this->getCustomMessage('draft_pending_single_message', $args);

      case WebformMessageManagerInterface::DRAFT_PENDING_MULTIPLE:
        $args = [':href' => $this->requestHandler->getUrl($webform, $source_entity, 'webform.user.drafts')->toString()];
        return $this->getCustomMessage('draft_pending_multiple_message', $args);

      case WebformMessageManagerInterface::PREVIOUS_SUBMISSION:
        $webform_submission = $this->entityStorage->getLastSubmission($webform, $source_entity, $this->currentUser);
        $args = [':href' => $this->requestHandler->getUrl($webform_submission, $source_entity, 'webform.user.submission')->toString()];
        return $this->getCustomMessage('previous_submission_message', $args);

      case WebformMessageManagerInterface::PREVIOUS_SUBMISSIONS:
        $args = [':href' => $this->requestHandler->getUrl($webform, $source_entity, 'webform.user.submissions')->toString()];
        return $this->getCustomMessage('previous_submissions_message', $args);
    }

    // Get hard-coded messages.
    switch ($key) {
      case WebformMessageManagerInterface::ADMIN_PAGE:
        return $this->t('Only webform administrators are allowed to access this page and create new submissions.');

      case WebformMessageManagerInterface::ADMIN_CLOSED:
        $t_args = [':href' => $webform->toUrl('settings-form')->toString()];
        return $this->t('This webform is <a href=":href">closed</a>. Only submission administrators are allowed to access this webform and create new submissions.', $t_args);

      case WebformMessageManagerInterface::ADMIN_ARCHIVED:
        $t_args = [':href' => $webform->toUrl('settings')->toString()];
        return $this->t('This webform is <a href=":href">archived</a>. Only submission administrators are allowed to access this webform and create new submissions.', $t_args);

      case WebformMessageManagerInterface::SUBMISSION_DEFAULT_CONFIRMATION:
        $t_args = ['%form' => ($source_entity) ? $source_entity->label() : $webform->label()];
        return $this->t('New submission added to %form.', $t_args);

      case WebformMessageManagerInterface::FORM_SAVE_EXCEPTION:
        $t_args = [
          ':handlers_href' => $webform->toUrl('handlers')->toString(),
          ':settings_href' => $webform->toUrl('settings')->toString(),
        ];
        return $this->t('This webform is currently not saving any submitted data. Please enable the <a href=":settings_href">saving of results</a> or add a <a href=":handlers_href">submission handler</a> to the webform.', $t_args);

      case WebformMessageManagerInterface::HANDLER_SUBMISSION_REQUIRED:
        $t_args = [':href' => $webform->toUrl('handlers')->toString()];
        return $this->t('This webform\'s <a href=":href">submission handlers</a> requires submissions to be saved to the database.', $t_args);

      case WebformMessageManagerInterface::SUBMISSION_UPDATED:
        $t_args = ['%form' => ($source_entity) ? $source_entity->label() : $webform->label()];
        return $this->t('Submission updated in %form.', $t_args);

      case WebformMessageManagerInterface::SUBMISSION_TEST:
        return $this->t("The below webform has been prepopulated with custom/random test data. When submitted, this information <strong>will still be saved</strong> and/or <strong>sent to designated recipients</strong>.");

      case WebformMessageManagerInterface::TEMPLATE_PREVIEW:
        $t_args = [':href' => $webform->toUrl('duplicate-form')->toString()];
        return $this->t('You are previewing the below template, which can be used to <a href=":href">create a new webform</a>. <strong>Submitted data will be ignored</strong>.', $t_args);

      case WebformMessageManagerInterface::PREPOPULATE_SOURCE_ENTITY_TYPE:
      case WebformMessageManagerInterface::PREPOPULATE_SOURCE_ENTITY_REQUIRED:
        return $this->t('This webform is not available. Please contact the site administrator.');

      case WebformMessageManagerInterface::PREVIOUS_SUBMISSION:
        $webform_submission = $this->entityStorage->getLastSubmission($webform, $source_entity, $this->currentUser);
        $args = [':href' => $this->requestHandler->getUrl($webform_submission, $source_entity, 'webform.user.submission')->toString()];
        return $this->getCustomMessage('previous_submission_message', $args);

      case WebformMessageManagerInterface::PREVIOUS_SUBMISSIONS:
        $args = [':href' => $this->requestHandler->getUrl($webform, $source_entity, 'webform.user.submissions')->toString()];
        return $this->getCustomMessage('previous_submissions_message', $args);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function log($key, $type = 'warning') {
    $webform = $this->webform;
    $context = [
      'link' => $webform->toLink($this->t('Edit'), 'settings')->toString(),
    ];

    switch ($key) {
      case WebformMessageManagerInterface::FORM_FILE_UPLOAD_EXCEPTION:
        $message = 'To support file uploads the saving of submission must be enabled. <strong>All uploaded load files would be lost</strong> Please either uncheck \'Disable saving of submissions\' or remove all the file upload elements.';
        break;

      case WebformMessageManagerInterface::FORM_SAVE_EXCEPTION:
        $context['%form'] = $webform->label();
        $message = '%form is not saving any submitted data and has been disabled.';
        break;

      case WebformMessageManagerInterface::PREPOPULATE_SOURCE_ENTITY_TYPE:
        $context['%form'] = $webform->label();
        $message = '%form prepopulated source entity is not valid.';
        break;

      case WebformMessageManagerInterface::PREPOPULATE_SOURCE_ENTITY_REQUIRED:
        $context['%form'] = $webform->label();
        $message = '%form prepopulated source entity is required.';
        break;

    }

    $this->logger->$type($message, $context);
  }

  /**
   * Get message from webform specific setting or global setting.
   *
   * @param string $key
   *   The name of webform settings message to be displayed.
   *
   * @return string|bool
   *   A message or FALSE when no message is found or the message
   *   is set to [none].
   */
  protected function getSetting($key) {
    $webform_settings = ($this->webform) ? $this->webform->getSettings() : [];
    if (!empty($webform_settings[$key])) {
      $value = $webform_settings[$key];
      if ($value === '[none]' || $value === (string) $this->t('[none]')) {
        return FALSE;
      }
      else {
        return $value;
      }
    }

    $default_settings = $this->configFactory->get('webform.settings')->get('settings');
    if (!empty($default_settings['default_' . $key])) {
      return $default_settings['default_' . $key];
    }
    return FALSE;
  }

  /**
   * Get custom message.
   *
   * @param string $key
   *   Message key.
   * @param array $arguments
   *   An array with placeholder replacements, keyed by placeholder.
   *
   * @return array|bool
   *   Renderable array or FALSE if custom message does not exist.
   */
  protected function getCustomMessage($key, array $arguments = []) {
    $setting = $this->getSetting($key);
    if (!$setting) {
      return FALSE;
    }

    // Replace tokens.
    $entity = $this->webformSubmission ?: $this->webform;
    $message = $this->tokenManager->replace($setting, $entity);

    // Replace arguments.
    if ($arguments) {
      $message = str_replace('href="#"', 'href=":href"', $message);
      $message = new FormattableMarkup($message, $arguments);
    }

    return WebformHtmlEditor::checkMarkup($message);
  }

}
