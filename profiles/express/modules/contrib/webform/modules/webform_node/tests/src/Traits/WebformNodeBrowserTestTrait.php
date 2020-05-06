<?php

namespace Drupal\Tests\webform_node\Traits;

use Drupal\node\NodeInterface;
use Drupal\webform\WebformInterface;

/**
 * Provides convenience methods for webform node browser tests.
 */
trait WebformNodeBrowserTestTrait {

  /**
   * Post a new submission to a webform node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A Webform node.
   * @param array $edit
   *   Submission values.
   * @param string $submit
   *   Value of the submit button whose click is to be emulated.
   * @param array $options
   *   Options to be forwarded to the url generator.
   *
   * @return int
   *   The created submission's sid.
   *
   * @see \Drupal\webform\Tests\WebformTestBase::postSubmission
   */
  protected function postNodeSubmission(NodeInterface $node, array $edit = [], $submit = NULL, array $options = []) {
    /** @var \Drupal\webform\WebformEntityReferenceManagerInterface $entity_reference_manager */
    $entity_reference_manager = \Drupal::service('webform.entity_reference_manager');

    $webform = $entity_reference_manager->getWebform($node);
    $submit = $this->getWebformSubmitButtonLabel($webform, $submit);
    $this->drupalPostForm('/node/' . $node->id(), $edit, $submit, $options);
    return $this->getLastSubmissionId($webform);
  }

  /**
   * Post a new test submission to a webform node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A Webform node.
   * @param array $edit
   *   Submission values.
   * @param string $submit
   *   Value of the submit button whose click is to be emulated.
   * @param array $options
   *   Options to be forwarded to the url generator.
   *
   * @return int
   *   The created submission's sid.
   *
   * @see \Drupal\webform\Tests\WebformTestBase::postSubmission
   */
  protected function postNodeSubmissionTest(NodeInterface $node, array $edit = [], $submit = NULL, array $options = []) {
    /** @var \Drupal\webform\WebformEntityReferenceManagerInterface $entity_reference_manager */
    $entity_reference_manager = \Drupal::service('webform.entity_reference_manager');

    $webform = $entity_reference_manager->getWebform($node);
    $submit = $this->getWebformSubmitButtonLabel($webform, $submit);
    $this->drupalPostForm('/node/' . $node->id() . '/webform/test', $edit, $submit, $options);
    return $this->getLastSubmissionId($webform);
  }

  /**
   * Create a webform node.
   *
   * @param string $webform_id
   *   A webform id.
   * @param array $settings
   *   (optional) An associative array of settings for the node, as used in
   *   entity_create().
   *
   * @return \Drupal\node\NodeInterface
   *   A webform node.
   */
  protected function createWebformNode($webform_id, array $settings = []) {
    $settings += ['type' => 'webform'];
    $node = $this->drupalCreateNode($settings);
    $node->webform->target_id = $webform_id;
    $node->webform->status = WebformInterface::STATUS_OPEN;
    $node->webform->open = '';
    $node->webform->close = '';
    $node->save();
    return $node;
  }

}
