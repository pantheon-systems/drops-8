<?php

namespace Drupal\webform_node\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\Controller\WebformSubmissionLogController;

/**
 * Returns responses for webform submission log routes.
 */
class WebformNodeSubmissionLogController extends WebformSubmissionLogController {

  /**
   * Wrapper that allows the $node to be used as $source_entity.
   */
  public function overview(WebformInterface $webform = NULL, WebformSubmissionInterface $webform_submission = NULL, EntityInterface $node = NULL) {
    return parent::overview($webform, $webform_submission, $node);
  }

}
