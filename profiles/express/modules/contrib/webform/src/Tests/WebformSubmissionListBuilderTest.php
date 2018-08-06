<?php

namespace Drupal\webform\Tests;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform submission list builder.
 *
 * @group Webform
 */
class WebformSubmissionListBuilderTest extends WebformTestBase {

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
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create users.
    $this->createUsers();
  }

  /**
   * Tests results.
   */
  public function testResults() {
    global $base_path;

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_submissions');
    /** @var \Drupal\webform\WebformSubmissionInterface[] $submissions */
    $submissions = array_values(\Drupal::entityTypeManager()->getStorage('webform_submission')->loadByProperties(['webform_id' => 'test_submissions']));

    // Login the normal user.
    $this->drupalLogin($this->ownWebformSubmissionUser);

    // Make the second submission to be starred (aka sticky).
    $submissions[1]->setSticky(TRUE)->save();

    $this->drupalLogin($this->adminSubmissionUser);

    /* Filter */

    $this->drupalGet('admin/structure/webform/manage/' . $webform->id() . '/results/submissions');

    // Check state options with totals.
    $this->assertRaw('<select data-drupal-selector="edit-state" id="edit-state" name="state" class="form-select"><option value="" selected="selected">All [3]</option><option value="starred">Starred [1]</option><option value="unstarred">Unstarred [2]</option></select>');

    // Check results with no filtering.
    $this->assertLinkByHref($submissions[0]->toUrl()->toString());
    $this->assertLinkByHref($submissions[1]->toUrl()->toString());
    $this->assertLinkByHref($submissions[2]->toUrl()->toString());
    $this->assertRaw($submissions[0]->getElementData('first_name'));
    $this->assertRaw($submissions[1]->getElementData('first_name'));
    $this->assertRaw($submissions[2]->getElementData('first_name'));
    $this->assertNoFieldById('edit-reset', 'reset');

    // Check results filtered by key(word).
    $this->drupalPostForm('admin/structure/webform/manage/' . $webform->id() . '/results/submissions', ['search' => $submissions[0]->getElementData('first_name')], t('Filter'));
    $this->assertUrl('admin/structure/webform/manage/' . $webform->id() . '/results/submissions?search=' . $submissions[0]->getElementData('first_name') . '&state=');
    $this->assertRaw($submissions[0]->getElementData('first_name'));
    $this->assertNoRaw($submissions[1]->getElementData('first_name'));
    $this->assertNoRaw($submissions[2]->getElementData('first_name'));
    $this->assertFieldById('edit-reset', 'Reset');

    // Check results filtered by state.
    $this->drupalPostForm('admin/structure/webform/manage/' . $webform->id() . '/results/submissions', ['state' => 'starred'], t('Filter'));
    $this->assertUrl('admin/structure/webform/manage/' . $webform->id() . '/results/submissions?search=&state=starred');
    $this->assertRaw('<option value="starred" selected="selected">Starred [1]</option>');
    $this->assertNoRaw($submissions[0]->getElementData('first_name'));
    $this->assertRaw($submissions[1]->getElementData('first_name'));
    $this->assertNoRaw($submissions[2]->getElementData('first_name'));
    $this->assertFieldById('edit-reset', 'Reset');

    /**************************************************************************/
    // Customize submission results.
    /**************************************************************************/

    // Check that created is visible and changed is hidden.
    $this->drupalGet('admin/structure/webform/manage/' . $webform->id() . '/results/submissions');
    $this->assertRaw('sort by Created');
    $this->assertNoRaw('sort by Changed');

    // Check that first name is before last name.
    $this->assertPattern('#First name.+Last name#s');

    // Check that no pager is being displayed.
    $this->assertNoRaw('<nav class="pager" role="navigation" aria-labelledby="pagination-heading">');

    // Check that table is sorted by serial.
    $this->assertRaw('<th specifier="serial" aria-sort="descending" class="is-active">');

    // Check the table results order by sid.
    $this->assertPattern('#Hillary.+Abraham.+George#ms');

    // Customize to results table.
    $edit = [
      'columns[created][checkbox]' => FALSE,
      'columns[changed][checkbox]' => TRUE,
      'columns[element__first_name][weight]' => '8',
      'columns[element__last_name][weight]' => '7',
      'sort' => 'element__first_name',
      'direction' => 'desc',
      'limit' => 20,
    ];
    $this->drupalPostForm('admin/structure/webform/manage/' . $webform->id() . '/results/submissions/custom', $edit, t('Save'));
    $this->assertRaw('The customized table has been saved.');

    // Check that sid is hidden and changed is visible.
    $this->drupalGet('admin/structure/webform/manage/' . $webform->id() . '/results/submissions');
    $this->assertNoRaw('sort by Created');
    $this->assertRaw('sort by Changed');

    // Check that first name is now after last name.
    $this->assertPattern('#Last name.+First name#ms');

    // Check the table results order by first name.
    $this->assertPattern('#Hillary.+George.+Abraham#ms');

    // Manually set the limit to 1.
    $webform->setState('results.custom.limit', 1);

    // Check that only one result (Hillary #2) is displayed with pager.
    $this->drupalGet('admin/structure/webform/manage/' . $webform->id() . '/results/submissions');
    $this->assertNoRaw($submissions[0]->getElementData('first_name'));
    $this->assertNoRaw($submissions[1]->getElementData('first_name'));
    $this->assertRaw($submissions[2]->getElementData('first_name'));
    $this->assertRaw('<nav class="pager" role="navigation" aria-labelledby="pagination-heading">');

    // Reset the limit to 20.
    $webform->setState('results.custom.limit', 20);

    // Check Header label and element value display.
    $this->drupalGet('admin/structure/webform/manage/' . $webform->id() . '/results/submissions');

    // Check user header and value.
    $this->assertRaw('<a href="' . $base_path . 'admin/structure/webform/manage/' . $webform->id() . '/results/submissions?sort=asc&amp;order=User" title="sort by User">User</a>');
    $this->assertRaw('<td class="priority-medium">Anonymous</td>');

    // Check date of birth.
    $this->assertRaw('<th specifier="element__dob"><a href="' . $base_path . 'admin/structure/webform/manage/' . $webform->id() . '/results/submissions?sort=asc&amp;order=Date%20of%20birth" title="sort by Date of birth">Date of birth</a></th>');
    $this->assertRaw('<td>Sunday, October 26, 1947</td>');

    // Display Header key and element raw.
    $webform->setState('results.custom.format', [
      'header_format' => 'key',
      'element_format' => 'raw',
    ]);

    $this->drupalGet('admin/structure/webform/manage/' . $webform->id() . '/results/submissions');

    // Check user header and value.
    $this->assertRaw('<a href="' . $base_path . 'admin/structure/webform/manage/' . $webform->id() . '/results/submissions?sort=asc&amp;order=uid" title="sort by uid">uid</a>');
    $this->assertRaw('<td class="priority-medium">0</td>');

    // Check date of birth.
    $this->assertRaw('<th specifier="element__dob"><a href="' . $base_path . 'admin/structure/webform/manage/' . $webform->id() . '/results/submissions?sort=asc&amp;order=dob" title="sort by dob">dob</a></th>');
    $this->assertRaw('<td>1947-10-26</td>');

    /**************************************************************************/
    // Customize user results.
    /**************************************************************************/

    $this->drupalLogin($this->ownWebformSubmissionUser);

    // Check view own submissions.
    $this->drupalget('/webform/' . $webform->id() . '/submissions');
    $this->assertRaw('<th specifier="serial" aria-sort="descending" class="is-active">');
    $this->assertRaw('<th specifier="created" class="priority-medium">');
    $this->assertRaw('<th specifier="remote_addr" class="priority-low">');

    // Display on first name and last name columns.
    $webform->setSetting('submission_user_columns', ['element__first_name', 'element__last_name'])
      ->save();

    // Check view own submissions only include first name and last name,
    $this->drupalget('/webform/' . $webform->id() . '/submissions');
    $this->assertNoRaw('<th specifier="serial" aria-sort="descending" class="is-active">');
    $this->assertNoRaw('<th specifier="created" class="priority-medium">');
    $this->assertNoRaw('<th specifier="remote_addr" class="priority-low">');
    $this->assertRaw('<th specifier="element__first_name" aria-sort="ascending" class="is-active">');
    $this->assertRaw('<th specifier="element__last_name">');
  }

}
