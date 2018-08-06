<?php

namespace Drupal\webform_node\Tests;

use Drupal\node\NodeInterface;
use Drupal\webform\Tests\WebformTestBase;
use Drupal\webform\WebformInterface;

/**
 * Base tests for webform node.
 */
abstract class WebformNodeTestBase extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform', 'webform_node'];

  /**
   * Post a new submission to a webform node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A Webform node.
   * @param array $edit
   *   Submission values.
   * @param string $submit
   *   Value of the submit button whose click is to be emulated.
   *
   * @return int
   *   The created submission's sid.
   *
   * @see \Drupal\webform\Tests\WebformTestBase::postSubmission
   */
  protected function postNodeSubmission(NodeInterface $node, array $edit = [], $submit = NULL) {
    /** @var \Drupal\webform\WebformEntityReferenceManagerInterface $entity_reference_manager */
    $entity_reference_manager = \Drupal::service('webform.entity_reference_manager');

    $webform = $entity_reference_manager->getWebform($node);
    $submit = $this->getWebformSubmitButtonLabel($webform, $submit);
    $this->drupalPostForm('node/' . $node->id(), $edit, $submit);
    return $this->getLastSubmissionId($webform);
  }

  /**
   * Create a webform node.
   *
   * @param string $webform_id
   *   A webform id.
   *
   * @return \Drupal\node\NodeInterface
   *   A webform node.
   */
  protected function createWebformNode($webform_id) {
    $node = $this->drupalCreateNode(['type' => 'webform']);
    $node->webform->target_id = $webform_id;
    $node->webform->status = WebformInterface::STATUS_OPEN;
    $node->webform->open = '';
    $node->webform->close = '';
    $node->save();
    return $node;
  }

}
