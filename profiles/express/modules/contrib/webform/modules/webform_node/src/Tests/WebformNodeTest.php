<?php

namespace Drupal\webform_node\Tests;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformInterface;

/**
 * Tests for webform node.
 *
 * @group WebformNode
 */
class WebformNodeTest extends WebformNodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block', 'webform', 'webform_node'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_limit', 'test_confirmation_inline'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create users.
    $this->createUsers();

    // Place webform test blocks.
    $this->placeWebformBlocks('webform_test_block_submission_limit');
  }

  /**
   * Tests webform node.
   */
  public function testNode() {
    $node = $this->createWebformNode('contact');

    /** @var \Drupal\webform\WebformEntityReferenceManagerInterface $entity_reference_manager */
    $entity_reference_manager = \Drupal::service('webform.entity_reference_manager');

    // Check table names.
    $this->assertEqual($entity_reference_manager->getTableNames(), [
      "node__webform" => 'webform',
      "node_revision__webform" => 'webform',
    ]);

    /**************************************************************************/
    // Webform node basic.
    /**************************************************************************/

    // Check contact webform.
    $this->drupalGet('node/' . $node->id());
    $this->assertRaw('id="webform-submission-contact-node-' . $node->id() . '-add-form"');
    $this->assertNoFieldByName('name', 'John Smith');

    // Check contact webform with default data.
    $node->webform->default_data = "name: 'John Smith'";
    $node->save();
    $this->drupalGet('node/' . $node->id());
    $this->assertFieldByName('name', 'John Smith');

    /**************************************************************************/
    // Webform node open and closed.
    /**************************************************************************/

    // Check contact webform closed.
    $node->webform->status = WebformInterface::STATUS_CLOSED;
    $node->save();
    $this->drupalGet('node/' . $node->id());
    $this->assertNoFieldByName('name', 'John Smith');
    $this->assertRaw('Sorry...This form is closed to new submissions.');

    /* Confirmation inline (test_confirmation_inline) */

    // Check confirmation inline webform.
    $node->webform->target_id = 'test_confirmation_inline';
    $node->webform->default_data = '';
    $node->webform->status = WebformInterface::STATUS_OPEN;
    $node->webform->open = '';
    $node->webform->close = '';
    $node->save();
    $this->postNodeSubmission($node);
    $this->assertRaw('This is a custom inline confirmation message.');

    /**************************************************************************/
    // Webform node scheduleD.
    /**************************************************************************/

    // Check scheduled to open.
    $node->webform->target_id = 'contact';
    $node->webform->status = WebformInterface::STATUS_SCHEDULED;
    $node->webform->open = date('Y-m-d\TH:i:s', strtotime('today +1 day'));
    $node->webform->close = '';
    $node->save();
    $this->drupalGet('node/' . $node->id());
    $this->assertRaw('This form has not yet been opened to submissions.');
    $this->assertNoFieldByName('name', 'John Smith');

    // Check scheduled and opened.
    $node->webform->target_id = 'contact';
    $node->webform->status = WebformInterface::STATUS_SCHEDULED;
    $node->webform->open = date('Y-m-d\TH:i:s', strtotime('today -1 day'));
    $node->webform->close = '';
    $node->save();
    $this->drupalGet('node/' . $node->id());
    $this->assertNoRaw('This form has not yet been opened to submissions.');
    $this->assertFieldByName('name');

    // Check scheduled and closed.
    $node->webform->target_id = 'contact';
    $node->webform->status = WebformInterface::STATUS_SCHEDULED;
    $node->webform->open = '';
    $node->webform->close = date('Y-m-d\TH:i:s', strtotime('today -1 day'));
    $node->save();
    $this->drupalGet('node/' . $node->id());
    $this->assertRaw('Sorry...This form is closed to new submissions.');
    $this->assertNoFieldByName('name');

    // Check scheduled and is open because open or close data was not set.
    $node->webform->target_id = 'contact';
    $node->webform->status = WebformInterface::STATUS_SCHEDULED;
    $node->webform->open = '';
    $node->webform->close = '';
    $node->save();
    $this->drupalGet('node/' . $node->id());
    $this->assertNoRaw('Sorry...This form is closed to new submissions.');
    $this->assertFieldByName('name');

    // Check that changes to global message clear the cache.
    $node->webform->target_id = 'contact';
    $node->webform->status = WebformInterface::STATUS_SCHEDULED;
    $node->webform->open = '';
    $node->webform->close = date('Y-m-d\TH:i:s', strtotime('today -1 day'));
    $node->save();
    $this->drupalGet('node/' . $node->id());

    \Drupal::configFactory()
      ->getEditable('webform.settings')
      ->set('settings.default_form_close_message', '{Custom closed message}')
      ->save();
    $this->drupalGet('node/' . $node->id());
    $this->assertRaw('{Custom closed message}');

    /**************************************************************************/
    // Submission limit (test_form_limit).
    /**************************************************************************/

    // Set per source entity total and user limit.
    // @see \Drupal\webform\Tests\WebformSubmissionFormSettingsTest::testSettings
    $node->webform->target_id = 'test_form_limit';
    $node->webform->default_data = '';
    $node->webform->open = '';
    $node->webform->close = '';
    $node->save();

    $limit_form = Webform::load('test_form_limit');
    $limit_form->setSettings([
      'limit_total' => NULL,
      'limit_user' => NULL,
      'entity_limit_total' => 3,
      'entity_limit_user' => 1,
      'limit_total_message' => 'Only 3 submissions are allowed.',
      'limit_user_message' => 'You are only allowed to have 1 submission for this webform.',
    ]);
    $limit_form->save();

    $this->drupalGet('node/' . $node->id());

    // Check submission limit blocks.
    $this->assertRaw('0 user + source entity submission(s)');
    $this->assertRaw('1 user + source entity limit');
    $this->assertRaw('0 webform + source entity submission(s)');
    $this->assertRaw('3 webform + source entity limit');

    // Create submission as authenticated user.
    $this->drupalLogin($this->normalUser);
    $this->postNodeSubmission($node);

    $this->drupalGet('node/' . $node->id());

    // Check per source entity user limit.
    $this->assertNoFieldByName('op', 'Submit');
    $this->assertRaw('You are only allowed to have 1 submission for this webform.');

    // Check submission limit blocks.
    $this->assertRaw('1 user + source entity submission(s)');
    $this->assertRaw('1 user + source entity limit');
    $this->assertRaw('1 webform + source entity submission(s)');
    $this->assertRaw('3 webform + source entity limit');

    $this->drupalLogout();

    // Create 2 submissions as root user, who can ignore submission limits.
    $this->drupalLogin($this->rootUser);
    $this->postNodeSubmission($node);
    $this->postNodeSubmission($node);
    $this->drupalLogout();

    $this->drupalLogin($this->normalUser);

    $this->drupalGet('node/' . $node->id());

    // Check per source entity total limit.
    $this->assertNoFieldByName('op', 'Submit');
    $this->assertRaw('Only 3 submissions are allowed.');
    $this->assertNoRaw('You are only allowed to have 1 submission for this webform.');

    // Check submission limit blocks.
    $this->assertRaw('1 user + source entity submission(s)');
    $this->assertRaw('1 user + source entity limit');
    $this->assertRaw('3 webform + source entity submission(s)');
    $this->assertRaw('3 webform + source entity limit');

    /**************************************************************************/
    // Prepopulate source entity.
    /**************************************************************************/

    $webform_contact = Webform::load('contact');

    $node->webform->target_id = 'contact';
    $node->webform->status = WebformInterface::STATUS_OPEN;
    $node->webform->default_data = "name: '{name}'";
    $node->webform->open = '';
    $node->webform->close = '';
    $node->save();

    $source_entity_options = ['query' => ['source_entity_type' => 'node', 'source_entity_id' => $node->id()]];

    // Check default data from source entity using query string.
    $this->drupalGet('webform/contact', $source_entity_options);
    $this->assertFieldByName('name', '{name}');

    // Check prepopulating source entity using query string.
    $edit = [
      'name' => 'name',
      'email' => 'example@example.com',
      'subject' => 'subject',
      'message' => 'message',
    ];
    $this->drupalPostForm('webform/contact', $edit, t('Send message'), $source_entity_options);
    $sid = $this->getLastSubmissionId($webform_contact);
    $submission = WebformSubmission::load($sid);
    $this->assertNotNull($submission->getSourceEntity());
    if ($submission->getSourceEntity()) {
      $this->assertEqual($submission->getSourceEntity()
        ->getEntityTypeId(), 'node');
      $this->assertEqual($submission->getSourceEntity()->id(), $node->id());
    }

    /**************************************************************************/
    // Check displaying link to webform.
    /**************************************************************************/

    // Set webform reference to be displayed as a link.
    $display_options = [
      'type' => 'webform_entity_reference_link',
      'settings' => [
        'label' => 'Register',
      ],
    ];
    $view_display = EntityViewDisplay::load('node.webform.default');
    $view_display->setComponent('webform', $display_options)->save();

    // Set default data.
    $node->webform->target_id = 'contact';
    $node->webform->status = WebformInterface::STATUS_OPEN;
    $node->webform->default_data = "name: '{name}'";
    $node->webform->open = '';
    $node->webform->close = '';
    $node->save();

    // Check 'Register' link.
    $this->drupalGet('node/' . $node->id());
    $this->assertLink('Register');

    // Check that link include source_entity_type and source_entity_id.
    $this->assertLinkByHref($webform_contact->toUrl('canonical', $source_entity_options)->toString());
  }

}
