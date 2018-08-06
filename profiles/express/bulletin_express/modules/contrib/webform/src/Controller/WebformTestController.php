<?php

namespace Drupal\webform\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformRequestInterface;
use Drupal\webform\WebformSubmissionGenerateInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for webform testing.
 */
class WebformTestController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;

  /**
   * Webform submission generation service.
   *
   * @var \Drupal\webform\WebformSubmissionGenerateInterface
   */
  protected $generate;

  /**
   * Constructs a WebformTestController object.
   *
   * @param \Drupal\webform\WebformRequestInterface $request_handler
   *   The webform request handler.
   * @param \Drupal\webform\WebformSubmissionGenerateInterface $submission_generate
   *   The webform submission generation service.
   */
  public function __construct(WebformRequestInterface $request_handler, WebformSubmissionGenerateInterface $submission_generate) {
    $this->requestHandler = $request_handler;
    $this->generate = $submission_generate;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
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
    $values = [];

    // Set source entity type and id.
    if ($source_entity) {
      $values['entity_type'] = $source_entity->getEntityTypeId();
      $values['entity_id'] = $source_entity->id();
    }

    if ($request->query->get('webform_id') == $webform->id()) {
      return $webform->getSubmissionForm($values);
    }

    // Generate date.
    $values['data'] = $this->generate->getData($webform);

    return $webform->getSubmissionForm($values);
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
