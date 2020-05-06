<?php

namespace Drupal\webform\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\webform\Plugin\WebformHandler\EmailWebformHandler;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformRequestInterface;
use Drupal\webform\WebformSubmissionGenerateInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides route responses for Webform testing.
 */
class WebformTestController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;

  /**
   * The webform submission generation service.
   *
   * @var \Drupal\webform\WebformSubmissionGenerateInterface
   */
  protected $generate;

  /**
   * Constructs a WebformTestController object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\webform\WebformRequestInterface $request_handler
   *   The webform request handler.
   * @param \Drupal\webform\WebformSubmissionGenerateInterface $submission_generate
   *   The webform submission generation service.
   */
  public function __construct(MessengerInterface $messenger, WebformRequestInterface $request_handler, WebformSubmissionGenerateInterface $submission_generate) {
    $this->messenger = $messenger;
    $this->requestHandler = $request_handler;
    $this->generate = $submission_generate;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('webform.request'),
      $container->get('webform_submission.generate')
    );
  }

  /**
   * Returns a webform to add a new test submission to a webform.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   The webform submission webform.
   */
  public function testForm(Request $request) {
    /** @var \Drupal\webform\WebformInterface $webform */
    /** @var \Drupal\Core\Entity\EntityInterface $source_entity */
    list($webform, $source_entity) = $this->requestHandler->getWebformEntities();

    // Test a single webform handler which is set via
    // ?_webform_handler={handler_id}.
    $test_webform_handler = $request->query->get('_webform_handler');
    if ($test_webform_handler) {
      // Make sure the handler exists.
      if (!$webform->getHandlers()->has($test_webform_handler)) {
        $t_args = [
          '%webform' => $webform->label(),
          '%handler' => $test_webform_handler,
        ];
        $this->messenger->addWarning($this->t('The %handler email/handler for the %webform webform does not exist.', $t_args));
        throw new AccessDeniedHttpException();
      }

      // Enable only the selected handler for testing
      // and disable all other handlers.
      $handlers = $webform->getHandlers();
      foreach ($handlers as $handler_id => $handler) {
        if ($handler_id === $test_webform_handler) {
          $handler->setStatus(TRUE);
          $t_args = [
            '%webform' => $webform->label(),
            '%handler' => $handler->label(),
            '@type' => ($handler instanceof EmailWebformHandler) ? $this->t('email') : $this->t('handler'),
          ];
          $this->messenger->addWarning($this->t('Testing the %webform webform %handler @type. <strong>All other emails/handlers are disabled.</strong>', $t_args));
        }
        else {
          $handler->setStatus(FALSE);
        }
      }

      // Set override to prevent the webform's altered handler statuses
      // from being saved.
      $webform->setOverride(TRUE);
    }

    // Set values.
    $values = [];

    // Set source entity type and id.
    if ($source_entity) {
      $values['entity_type'] = $source_entity->getEntityTypeId();
      $values['entity_id'] = $source_entity->id();
    }

    return $webform->getSubmissionForm($values, 'test');
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform.
   *
   * @return string
   *   The webform label as a render array.
   */
  public function title(WebformInterface $webform) {
    /** @var \Drupal\webform\WebformInterface $webform */
    /** @var \Drupal\Core\Entity\EntityInterface $source_entity */
    list($webform, $source_entity) = $this->requestHandler->getWebformEntities();
    return $this->t('Testing %title webform', ['%title' => ($source_entity) ? $source_entity->label() : $webform->label()]);
  }

}
