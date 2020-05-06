<?php

namespace Drupal\webform\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AnnounceCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\webform\Element\WebformHtmlEditor;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformRequestInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for Webform submissions.
 */
class WebformSubmissionController extends ControllerBase {

  /**
   * The renderer service.
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
   * Constructs a WebformSubmissionController object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\webform\WebformRequestInterface $request_handler
   *   The webform request handler.
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The webform token manager.
   */
  public function __construct(RendererInterface $renderer, WebformRequestInterface $request_handler, WebformTokenManagerInterface $token_manager) {
    $this->renderer = $renderer;
    $this->requestHandler = $request_handler;
    $this->tokenManager = $token_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('webform.request'),
      $container->get('webform.token_manager')
    );
  }

  /**
   * Toggle webform submission sticky.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An Ajax response that toggle the sticky icon.
   */
  public function sticky(WebformSubmissionInterface $webform_submission) {
    // Toggle sticky.
    $webform_submission->setSticky(!$webform_submission->isSticky())->save();

    // Get selector.
    $selector = '#webform-submission-' . $webform_submission->id() . '-sticky';

    $response = new AjaxResponse();

    // Update sticky.
    $response->addCommand(new HtmlCommand($selector, static::buildSticky($webform_submission)));

    // Announce sticky status.
    $t_args = ['@label' => $webform_submission->label()];
    $text = $webform_submission->isSticky() ? $this->t('@label flagged/starred.', $t_args) : $this->t('@label unflagged/unstarred.', $t_args);
    $response->addCommand(new AnnounceCommand($text));

    return $response;
  }

  /**
   * Toggle webform submission locked.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An Ajax response that toggle the lock icon.
   */
  public function locked(WebformSubmissionInterface $webform_submission) {
    // Toggle locked.
    $webform_submission->setLocked(!$webform_submission->isLocked())->save();

    // Get selector.
    $selector = '#webform-submission-' . $webform_submission->id() . '-locked';

    $response = new AjaxResponse();

    // Update lock.
    $response->addCommand(new HtmlCommand($selector, static::buildLocked($webform_submission)));

    // Announce lock status.
    $t_args = ['@label' => $webform_submission->label()];
    $text = $webform_submission->isLocked() ? $this->t('@label locked.', $t_args) : $this->t('@label unlocked.', $t_args);
    $response->addCommand(new AnnounceCommand($text));
    return $response;
  }

  /**
   * Build sticky icon.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return \Drupal\Component\Render\FormattableMarkup
   *   Sticky icon.
   */
  public static function buildSticky(WebformSubmissionInterface $webform_submission) {
    $t_args = ['@label' => $webform_submission->label()];
    $args = [
      '@state' => $webform_submission->isSticky() ? 'on' : 'off',
      '@label' => $webform_submission->isSticky() ? t('Unstar/Unflag @label', $t_args) : t('Star/flag @label', $t_args),
    ];
    return new FormattableMarkup('<span class="webform-icon webform-icon-sticky webform-icon-sticky--@state"></span><span class="visually-hidden">@label</span>', $args);
  }

  /**
   * Build locked icon.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return \Drupal\Component\Render\FormattableMarkup
   *   Locked icon.
   */
  public static function buildLocked(WebformSubmissionInterface $webform_submission) {
    $t_args = ['@label' => $webform_submission->label()];
    $args = [
      '@state' => $webform_submission->isLocked() ? 'on' : 'off',
      '@label' => $webform_submission->isLocked() ? t('Unlock @label', $t_args) : t('Lock @label', $t_args),
    ];
    return new FormattableMarkup('<span class="webform-icon webform-icon-lock webform-icon-locked--@state"></span><span class="visually-hidden">@label</span>', $args);
  }

  /**
   * Returns a webform submissions's access denied page.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return array
   *   A renderable array containing an access denied page.
   */
  public function accessDenied(WebformInterface $webform, WebformSubmissionInterface $webform_submission) {
    // Message.
    $config = $this->config('webform.settings');
    $message = $webform->getSetting('submission_access_denied_message')
      ?: $config->get('settings.default_submission_access_denied_message');
    $message = $this->tokenManager->replace($message, $webform_submission);

    // Attributes.
    $attributes = $webform->getSetting('submission_access_denied_attributes');
    $attributes['class'][] = 'webform-submission-access-denied';

    // Build message.
    $build = [
      '#type' => 'container',
      '#attributes' => $attributes,
      'message' => WebformHtmlEditor::checkMarkup($message),
    ];

    // Add config and webform to cache contexts.
    $this->renderer->addCacheableDependency($build, $config);
    $this->renderer->addCacheableDependency($build, $webform);

    return $build;
  }

  /**
   * Returns a webform 's access denied title.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   The webform's access denied title.
   */
  public function accessDeniedTitle(WebformInterface $webform) {
    return $webform->getSetting('submission_access_denied_title') ?: $this->t('Access denied');
  }

}
