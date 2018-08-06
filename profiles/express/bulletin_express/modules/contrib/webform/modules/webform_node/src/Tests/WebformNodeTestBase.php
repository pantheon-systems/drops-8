<?php

namespace Drupal\webform_node\Tests;

use Drupal\node\NodeInterface;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Tests\WebformTestBase;
use Drupal\webform\Plugin\Field\FieldType\WebformEntityReferenceItem;
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
    $webform_field_name = WebformEntityReferenceItem::getEntityWebformFieldName($node);
    $webform_id = $node->$webform_field_name->target_id;
    $webform = Webform::load($webform_id);
    $submit = $submit ?: $webform->getSetting('form_submit_label') ?: t('Submit');
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
