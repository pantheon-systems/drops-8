<?php

namespace Drupal\webform_attachment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform_attachment\Plugin\WebformElement\WebformAttachmentBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a controller to return a webform attachment.
 */
class WebformAttachmentController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Element info.
   *
   * @var \Drupal\Core\Render\ElementInfoManager
   */
  protected $elementInfo;

  /**
   * A webform element plugin manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * Constructs a WebformAttachmentController object.
   *
   * @param \Drupal\Core\Render\ElementInfoManagerInterface $element_info
   *   The element info manager.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   A webform element plugin manager.
   */
  public function __construct(ElementInfoManagerInterface $element_info, WebformElementManagerInterface $element_manager) {
    $this->elementInfo = $element_info;
    $this->elementManager = $element_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.element_info'),
      $container->get('plugin.manager.webform.element')
    );
  }

  /**
   * Response callback to download an attachment.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param string $element
   *   The attachment element webform key.
   * @param string $filename
   *   The attachment filename.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A response containing the attachment's file.
   */
  public function download(WebformInterface $webform, WebformSubmissionInterface $webform_submission, $element, $filename) {
    // Make sure the webform id and submission webform id match.
    if ($webform->id() !== $webform_submission->getWebform()->id()) {
      throw new NotFoundHttpException();
    }

    // Get the webform element and plugin.
    $element = $webform_submission->getWebform()->getElement($element) ?: [];
    $element_plugin = $this->elementManager->getElementInstance($element, $webform_submission);

    // Make sure the element is a webform attachment.
    if (!$element_plugin instanceof WebformAttachmentBase) {
      throw new NotFoundHttpException();
    }

    // Make sure element #access is not FALSE.
    // The #private property is used to to set #access to FALSE.
    // @see \Drupal\webform\Entity\Webform::initElementsRecursive
    if (isset($element['#access']) && $element['#access'] === FALSE) {
      throw new AccessDeniedHttpException();
    }

    // Make sure the current user can view the element.
    if (!$element_plugin->checkAccessRules('view', $element)) {
      throw new AccessDeniedHttpException();
    }

    /** @var \Drupal\webform_attachment\Element\WebformAttachmentInterface $element_info */
    // Get base form element for webform element derivatives.
    // @see \Drupal\webform_entity_print\Plugin\Derivative\WebformEntityPrintWebformElementDeriver
    list($type) = explode(':', $element['#type']);
    $element_info = $this->elementInfo->createInstance($type);

    // Get attachment information.
    $attachment_name = $element_info::getFileName($element, $webform_submission);
    $attachment_mime = $element_info::getFileMimeType($element, $webform_submission);
    $attachment_content = $element_info::getFileContent($element, $webform_submission);
    $attachment_size = strlen($attachment_content);
    $attachment_download = (!empty($element['#download'])) ? 'attachment;' : '';

    // Make sure the attachment can be downloaded.
    if (empty($attachment_name) || empty($attachment_content) || empty($attachment_mime)) {
      throw new NotFoundHttpException();
    }

    // Return the file.
    $headers = [
      'Content-Length' => $attachment_size,
      'Content-Type' => $attachment_mime,
      'Content-Disposition' => $attachment_download . 'filename="' . $filename . '"',
    ];
    return new Response($attachment_content, 200, $headers);
  }

}
