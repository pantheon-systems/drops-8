<?php

namespace Drupal\webform;

use Drupal\Core\Session\AccountInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\Core\StringTranslation\StringTranslationTrait;
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
   * The token manager.
   *
   * @var \Drupal\webform\WebformTranslationManagerInterface
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
   * Constructs a WebformMessageManager object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\webform\WebformRequestInterface $request_handler
   *   The webform request handler.
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The token manager.
   */
  public function __construct(AccountInterface $current_user, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger, RendererInterface $renderer, WebformRequestInterface $request_handler, WebformTokenManagerInterface $token_manager) {
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
    $this->entityStorage = $entity_type_manager->getStorage('webform_submission');
    $this->logger = $logger;
    $this->renderer = $renderer;
    $this->requestHandler = $request_handler;
    $this->tokenManager = $token_manager;
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
  public function setWebformSubmission(WebformSubmissionInterface $webform_submission = NULL) {
    $this->webformSubmission = $webform_submission;
    if ($webform_submission && empty($this->webform)) {
      $this->webform = $webform_submission->getWebform();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function display($key, $type = 'status') {
    $build = $this->build($key);
    // Do not display message via Ajax request.
    if ($build && !$this->requestHandler->isAjax()) {
      drupal_set_message($this->renderer->renderPlain($build), $type);
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build($key) {
    if ($message = $this->get($key)) {
      return [
        '#markup' => $message,
        '#allowed_tags' => Xss::getAdminTagList(),
      ];
    }
    else {
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get($key) {
    $webform_settings = ($this->webform) ? $this->webform->getSettings() : [];
    $entity = $this->webformSubmission ?: $this->webform;
    if (!empty($webform_settings[$key])) {
      return $this->tokenManager->replace($webform_settings[$key], $entity);
    }

    $default_settings = $this->configFactory->get('webform.settings')->get('settings');
    if (!empty($default_settings['default_' . $key])) {
      return $this->tokenManager->replace($default_settings['default_' . $key], $entity);
    }

    $webform = $this->webform;
    $source_entity = $this->sourceEntity;

    $t_args = [
      '%form' => ($source_entity) ? $source_entity->label() : $webform->label(),
      ':handlers_href' => $webform->toUrl('handlers-form')->toString(),
      ':settings_href' => $webform->toUrl('settings-form')->toString(),
      ':duplicate_href' => $webform->toUrl('duplicate-form')->toString(),
    ];

    switch ($key) {
      case WebformMessageManagerInterface::ADMIN_ACCESS:
        return $this->t('This webform is <a href=":settings_href">closed</a>. Only submission administrators are allowed to access this webform and create new submissions.', $t_args);

      case WebformMessageManagerInterface::SUBMISSION_DEFAULT_CONFIRMATION:
        return $this->t('New submission added to %form.', $t_args);

      case WebformMessageManagerInterface::FORM_SAVE_EXCEPTION:
        return $this->t('This webform is currently not saving any submitted data. Please enable the <a href=":settings_href">saving of results</a> or add a <a href=":handlers_href">submission handler</a> to the webform.', $t_args);

      case WebformMessageManagerInterface::HANDLER_SUBMISSION_REQUIRED:
        return $this->t('This webform\'s <a href=":handlers_href">submission handlers</a> requires submissions to be saved to the database.', $t_args);

      case WebformMessageManagerInterface::SUBMISSION_PREVIOUS:
        $webform_submission = $this->entityStorage->getLastSubmission($webform, $source_entity, $this->currentUser);
        $t_args[':submission_href'] = $this->requestHandler->getUrl($webform_submission, $source_entity, 'webform.user.submission')->toString();
        return $this->t('You have already submitted this webform.') . ' ' . $this->t('<a href=":submission_href">View your previous submission</a>.', $t_args);

      case WebformMessageManagerInterface::SUBMISSIONS_PREVIOUS:
        $t_args[':submissions_href'] = $this->requestHandler->getUrl($webform, $source_entity, 'webform.user.submissions')->toString();
        return $this->t('You have already submitted this webform.') . ' ' . $this->t('<a href=":submissions_href">View your previous submissions</a>.', $t_args);

      case WebformMessageManagerInterface::SUBMISSION_UPDATED:
        return $this->t('Submission updated in %form.', $t_args);

      case WebformMessageManagerInterface::SUBMISSION_TEST:
        return $this->t("The below webform has been prepopulated with custom/random test data. When submitted, this information <strong>will still be saved</strong> and/or <strong>sent to designated recipients</strong>.", $t_args);

      case WebformMessageManagerInterface::TEMPLATE_PREVIEW:
        return $this->t('You are previewing the below template, which can be used to <a href=":duplicate_href">create a new webform</a>. <strong>Submitted data will be ignored</strong>.', $t_args);

      default:
        return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function log($key, $type = 'warning') {
    $webform = $this->webform;
    $context = [
      'link' => $webform->toLink($this->t('Edit'), 'edit-form')->toString(),
    ];

    switch ($key) {
      case WebformMessageManagerInterface::FORM_FILE_UPLOAD_EXCEPTION:
        $message = 'To support file uploads the saving of submission must be enabled. <strong>All uploaded load files would be lost</strong> Please either uncheck \'Disable saving of submissions\' or remove all the file upload elements.';
        break;

      case WebformMessageManagerInterface::FORM_SAVE_EXCEPTION:
        $context['%form'] = $webform->label();
        $message = '%form is not saving any submitted data and has been disabled.';
        break;
    }

    $this->logger->$type($message, $context);
  }

}
