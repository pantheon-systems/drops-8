<?php

namespace Drupal\webform_scheduled_email\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform_scheduled_email\WebformScheduledEmailManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for webform scheduled email.
 */
class WebformScheduledEmailController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The webform scheduled email manager.
   *
   * @var \Drupal\webform_scheduled_email\WebformScheduledEmailManagerInterface
   */
  protected $manager;

  /**
   * Constructs a WebformScheduledEmailController object.
   *
   * @param \Drupal\webform_scheduled_email\WebformScheduledEmailManagerInterface $manager
   *   The webform scheduled email manager.
   */
  public function __construct(WebformScheduledEmailManagerInterface $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('webform_scheduled_email.manager')
    );
  }

  /**
   * Runs cron task for webform scheduled email handler.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform containg a scheduled email handler.
   * @param string|null $handler_id
   *   A webform handler id.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirection to the webform handlers page.
   */
  public function cron(WebformInterface $webform, $handler_id) {
    $stats = $this->manager->cron($webform, $handler_id);
    drupal_set_message($this->t($stats['_message'], $stats['_context']));
    return new RedirectResponse($webform->toUrl('handlers')->toString());
  }

}
