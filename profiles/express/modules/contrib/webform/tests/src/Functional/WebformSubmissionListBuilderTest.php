<?php

namespace Drupal\Tests\webform\Functional;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform submission list builder.
 *
 * @group Webform
 */
class WebformSubmissionListBuilderTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'webform', 'webform_test_submissions'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_submissions'];

  /**
   * Tests results.
   */
  public function testResults() {
    $own_submission_user = $this->drupalCreateUser([
      'view own webform submission',
      'edit own webform submission',
      'delete own webform submission',
      'access webform submission user',
    ]);

    $admin_submission_user = $this->drupalCreateUser([
      'administer webform submission',
    ]);

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_submissions');
    $this->webform = $webform;

    /** @var \Drupal\webform\WebformSubmissionInterface[] $submissions */
    $submissions = array_values(\Drupal::entityTypeManager()->getStorage('webform_submission')->loadByProperties(['webform_id' => 'test_submissions']));

    /**************************************************************************/

    // Login the own submission user.
    $this->drupalLogin($own_submission_user);

    // Make the second submission to be starred (aka sticky).
    $submissions[1]->setSticky(TRUE)->save();

    // Make the third submission to be locked.
    $submissions[2]->setLocked(TRUE)->save();

    $this->drupalLogin($admin_submission_user);

    /* Filter */

    $this->drupalGet('/admin/structure/webform/manage/' . $webform->id() . '/results/submissions');

    // Check state options with totals.
    $this->assertRaw('<select data-drupal-selector="edit-state" id="edit-state" name="state" class="form-select"><option value="" selected="selected">All [4]</option><option value="starred">Starred [1]</option><option value="unstarred">Unstarred [3]</option><option value="locked">Locked [1]</option><option value="unlocked">Unlocked [3]</option></select>');

    // Check results with no filtering.
    $this->assertLinkByHref($submissions[0]->toUrl()->toString());
    $this->assertLinkByHref($submissions[1]->toUrl()->toString());
    $this->assertLinkByHref($submissions[2]->toUrl()->toString());
    $this->assertRaw($submissions[0]->getElementData('first_name'));
    $this->assertRaw($submissions[1]->getElementData('first_name'));
    $this->assertRaw($submissions[2]->getElementData('first_name'));
    $this->assertNoFieldById('edit-reset', 'reset');

    // Check results filtered by uuid.
    $this->drupalPostForm('/admin/structure/webform/manage/' . $webform->id() . '/results/submissions', ['search' => $submissions[0]->get('uuid')->value], t('Filter'));
    $this->assertUrl('admin/structure/webform/manage/' . $webform->id() . '/results/submissions?search=' . $submissions[0]->get('uuid')->value);
    $this->assertRaw($submissions[0]->getElementData('first_name'));
    $this->assertNoRaw($submissions[1]->getElementData('first_name'));
    $this->assertNoRaw($submissions[2]->getElementData('first_name'));

    // Check results filtered by key(word).
    $this->drupalPostForm('/admin/structure/webform/manage/' . $webform->id() . '/results/submissions', ['search' => $submissions[0]->getElementData('first_name')], t('Filter'));
    $this->assertUrl('admin/structure/webform/manage/' . $webform->id() . '/results/submissions?search=' . $submissions[0]->getElementData('first_name'));
    $this->assertRaw($submissions[0]->getElementData('first_name'));
    $this->assertNoRaw($submissions[1]->getElementData('first_name'));
    $this->assertNoRaw($submissions[2]->getElementData('first_name'));
    $this->assertFieldById('edit-reset', 'Reset');

    // Check results filtered by state:starred.
    $this->drupalPostForm('/admin/structure/webform/manage/' . $webform->id() . '/results/submissions', ['state' => 'starred'], t('Filter'));
    $this->assertUrl('admin/structure/webform/manage/' . $webform->id() . '/results/submissions?state=starred');
    $this->assertRaw('<option value="starred" selected="selected">Starred [1]</option>');
    $this->assertNoRaw($submissions[0]->getElementData('first_name'));
    $this->assertRaw($submissions[1]->getElementData('first_name'));
    $this->assertNoRaw($submissions[2]->getElementData('first_name'));
    $this->assertFieldById('edit-reset', 'Reset');

    // Check results filtered by state:starred.
    $this->drupalPostForm('/admin/structure/webform/manage/' . $webform->id() . '/results/submissions', ['state' => 'locked'], t('Filter'));
    $this->assertUrl('admin/structure/webform/manage/' . $webform->id() . '/results/submissions?state=locked');
    $this->assertRaw('<option value="locked" selected="selected">Locked [1]</option>');
    $this->assertNoRaw($submissions[0]->getElementData('first_name'));
    $this->assertNoRaw($submissions[1]->getElementData('first_name'));
    $this->assertRaw($submissions[2]->getElementData('first_name'));
    $this->assertFieldById('edit-reset', 'Reset');
  }

}
