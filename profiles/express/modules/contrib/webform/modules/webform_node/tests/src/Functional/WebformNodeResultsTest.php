<?php

namespace Drupal\Tests\webform_node\Functional;

use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformInterface;

/**
 * Tests for webform node results.
 *
 * @group WebformNode
 */
class WebformNodeResultsTest extends WebformNodeBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block', 'webform', 'webform_node'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Place blocks.
    $this->placeBlocks();
  }

  /**
   * Tests webform node results.
   */
  public function testResults() {
    global $base_path;

    $normal_user = $this->drupalCreateUser();

    $admin_user = $this->drupalCreateUser([
      'administer webform',
    ]);

    $admin_submission_user = $this->drupalCreateUser([
      'administer webform submission',
    ]);

    /** @var \Drupal\webform\WebformSubmissionStorageInterface $submission_storage */
    $submission_storage = \Drupal::entityTypeManager()->getStorage('webform_submission');

    /**************************************************************************/

    $webform = Webform::load('contact');

    // Create node.
    $node = $this->drupalCreateNode(['type' => 'webform']);
    $nid = $node->id();

    /* Webform entity reference */

    // Check access denied to webform results.
    $this->drupalLogin($admin_submission_user);
    $this->drupalGet('/node/' . $node->id() . '/webform/results/submissions');
    $this->assertResponse(403);

    // Set Node webform to the contact webform.
    $node->webform->target_id = 'contact';
    $node->webform->status = WebformInterface::STATUS_OPEN;
    $node->save();

    /* Submission management */

    // Generate 3 node submissions and 3 webform submissions.
    $this->drupalLogin($normal_user);
    $node_sids = [];
    $webform_sids = [];
    for ($i = 1; $i <= 3; $i++) {
      $edit = [
        'name' => "node$i",
        'email' => "node$i@example.com",
        'subject' => "Node $i subject",
        'message' => "Node $i message",
      ];
      $node_sids[$i] = $this->postNodeSubmission($node, $edit);
      $edit = [
        'name' => "webform$i",
        'email' => "webform$i@example.com",
        'subject' => "Webform $i subject",
        'message' => "Webform $i message",
      ];
      $webform_sids[$i] = $this->postSubmission($webform, $edit);
    }

    // Check that 6 submission were created.
    $this->assertEqual($submission_storage->getTotal($webform, $node), 3);
    $this->assertEqual($submission_storage->getTotal($webform), 6);

    // Check webform node results.
    $this->drupalLogin($admin_submission_user);
    $node_route_parameters = ['node' => $node->id(), 'webform_submission' => $node_sids[1]];
    $node_submission_url = Url::fromRoute('entity.node.webform_submission.canonical', $node_route_parameters);
    $node_submission_title = $node->label() . ': Submission #' . $node_sids[1];
    $webform_submission_route_parameters = ['webform' => 'contact', 'webform_submission' => $node_sids[1]];
    $webform_submission_url = Url::fromRoute('entity.webform_submission.canonical', $webform_submission_route_parameters);

    $this->drupalGet('/node/' . $node->id() . '/webform/results/submissions');
    $this->assertResponse(200);
    $this->assertRaw('<h1 class="page-title">' . $node->label() . '</h1>');
    $this->assertNoRaw('<h1 class="page-title">' . $webform->label() . '</h1>');
    $this->assertRaw(('<a href="' . $node_submission_url->toString() . '" title="' . Html::escape($node_submission_title) . '" aria-label="' . Html::escape($node_submission_title) . '">' . $node_sids[1] . '</a>'));
    $this->assertNoRaw(('<a href="' . $webform_submission_url->toString() . '">' . $webform_sids[1] . '</a>'));

    // Check webform node title.
    $this->drupalGet('/node/' . $node->id() . '/webform/submission/' . $node_sids[1]);
    $this->assertRaw($node->label() . ': Submission #' . $node_sids[1]);
    $this->drupalGet('/node/' . $node->id() . '/webform/submission/' . $node_sids[2]);
    $this->assertRaw($node->label() . ': Submission #' . $node_sids[2]);

    // Check webform node navigation.
    $this->drupalGet('/node/' . $node->id() . '/webform/submission/' . $node_sids[1]);
    $node_route_parameters = ['node' => $node->id(), 'webform_submission' => $node_sids[2]];
    $node_submission_url = Url::fromRoute('entity.node.webform_submission.canonical', $node_route_parameters);
    $this->assertRaw('<a href="' . $node_submission_url->toString() . '" rel="next" title="Go to next page">Next submission <b>›</b></a>');

    // Check webform node saved draft.
    $webform->setSetting('draft', WebformInterface::DRAFT_AUTHENTICATED);
    $webform->save();

    // Check webform saved draft.
    $this->drupalLogin($normal_user);
    $edit = [
      'name' => "nodeDraft",
      'email' => "nodeDraft@example.com",
      'subject' => "Node draft subject",
      'message' => "Node draft message",
    ];
    $this->drupalPostForm('/node/' . $node->id(), $edit, t('Save Draft'));
    $this->drupalGet('/node/' . $node->id());
    $this->assertRaw('A partially-completed form was found. Please complete the remaining portions.');
    $this->drupalGet('/webform/contact');
    $this->assertNoRaw('A partially-completed form was found. Please complete the remaining portions.');

    /* Table customization */

    // Check that access is denied to custom results table.
    $this->drupalLogin($admin_submission_user);
    $this->drupalGet('/admin/structure/webform/manage/' . $webform->id() . '/results/submissions/custom');
    $this->assertResponse(403);

    // Check that access is allowed to custom results table.
    $this->drupalLogin($admin_user);
    $this->drupalGet('/admin/structure/webform/manage/' . $webform->id() . '/results/submissions/custom');
    $this->assertResponse(200);

    // Check default node results table.
    $this->drupalGet('/node/' . $node->id() . '/webform/results/submissions');
    $this->assertRaw('<th specifier="created" class="priority-medium is-active" aria-sort="descending">');
    $this->assertRaw('sort by Created');
    $this->assertNoRaw('sort by Changed');

    // Customize to main webform's results table.
    $edit = [
      'columns[created][checkbox]' => FALSE,
      'columns[changed][checkbox]' => TRUE,
      'direction' => 'asc',
      'limit' => 20,
      'default' => TRUE,
    ];
    $this->drupalPostForm('/admin/structure/webform/manage/' . $webform->id() . '/results/submissions/custom', $edit, t('Save'));
    $this->assertRaw('The customized table has been saved.');

    // Check that the webform node's results table is now customized.
    $this->drupalGet('/node/' . $node->id() . '/webform/results/submissions');
    $this->assertRaw('<th specifier="serial" aria-sort="ascending" class="is-active">');
    $this->assertNoRaw('sort by Created');
    $this->assertRaw('sort by Changed');

    $this->drupalLogout();

    /* Access control */

    // Create any and own user accounts.
    $any_user = $this->drupalCreateUser([
      'access content',
      'view webform submissions any node',
      'edit webform submissions any node',
      'delete webform submissions any node',
    ]);
    $own_user = $this->drupalCreateUser([
      'access content',
      'view webform submissions own node',
      'edit webform submissions own node',
      'delete webform submissions own node',
    ]);

    // Check accessing results posted to any webform node.
    $this->drupalLogin($any_user);
    $this->drupalGet('/node/' . $node->id() . '/webform/results/submissions');
    $this->assertResponse(200);
    foreach ($node_sids as $node_sid) {
      $this->assertLinkByHref("{$base_path}node/{$nid}/webform/submission/{$node_sid}");
    }

    // Check accessing results posted to own webform node.
    $this->drupalLogin($own_user);
    $this->drupalGet('/node/' . $node->id() . '/webform/results/submissions');
    $this->assertResponse(403);

    $node->setOwnerId($own_user->id())->save();
    $this->drupalGet('/node/' . $node->id() . '/webform/results/submissions');
    $this->assertResponse(200);
    foreach ($node_sids as $node_sid) {
      $this->assertLinkByHref("{$base_path}node/{$nid}/webform/submission/{$node_sid}");
    }

    // Check deleting webform node results.
    $this->drupalPostForm('/node/' . $node->id() . '/webform/results/clear', ['confirm' => TRUE], t('Clear'));
    $this->assertEqual($submission_storage->getTotal($webform, $node), 0);
    $this->assertEqual($submission_storage->getTotal($webform), 3);
  }

}
