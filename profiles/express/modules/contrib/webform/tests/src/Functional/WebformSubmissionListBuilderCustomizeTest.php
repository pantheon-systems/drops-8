<?php

namespace Drupal\Tests\webform\Functional;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform submission list builder.
 *
 * @group Webform
 */
class WebformSubmissionListBuilderCustomizeTest extends WebformBrowserTestBase {

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
   * Tests customize.
   */
  public function testCustomize() {
    global $base_path;

    $admin_user = $this->drupalCreateUser([
      'administer webform',
    ]);

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

    /** @var \Drupal\webform\WebformSubmissionInterface[] $submissions */
    $submissions = array_values(\Drupal::entityTypeManager()->getStorage('webform_submission')->loadByProperties(['webform_id' => 'test_submissions']));

    /** @var \Drupal\user\UserDataInterface $user_data */
    $user_data = \Drupal::service('user.data');

    /**************************************************************************/
    // Customize default table.
    /**************************************************************************/

    // Check that access is denied to custom results default table.
    $this->drupalLogin($admin_submission_user);
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions/custom');
    $this->assertResponse(403);

    // Check that access is denied to custom results user table.
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions/custom/user');
    $this->assertResponse(403);

    // Check that access is allowed to custom results default table.
    $this->drupalLogin($admin_user);
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions/custom');
    $this->assertResponse(200);

    // Check that access is denied to custom results user table.
    $this->drupalLogin($admin_user);
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions/custom/user');
    $this->assertResponse(403);

    // Check that created is visible and changed is hidden.
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions');
    $this->assertRaw('sort by Created');
    $this->assertNoRaw('sort by Changed');

    // Check that first name is before last name.
    $this->assertPattern('#First name.+Last name#s');

    // Check that no pager is being displayed.
    $this->assertNoRaw('<nav class="pager" role="navigation" aria-labelledby="pagination-heading">');

    // Check that table is sorted by created.
    $this->assertRaw('<th specifier="created" class="priority-medium is-active" aria-sort="descending">');

    // Check the table results order by sid.
    $this->assertPattern('#Hillary.+Abraham.+George#ms');

    // Check the table links to canonical view.
    $this->assertRaw('data-webform-href="' . $submissions[0]->toUrl()->toString() . '"');
    $this->assertRaw('data-webform-href="' . $submissions[1]->toUrl()->toString() . '"');
    $this->assertRaw('data-webform-href="' . $submissions[2]->toUrl()->toString() . '"');

    // Check webform state.
    $actual_state = \Drupal::state()->get('webform.webform.test_submissions');
    $this->assertNull($actual_state);

    // Customize to results default table.
    $edit = [
      'columns[created][checkbox]' => FALSE,
      'columns[changed][checkbox]' => TRUE,
      'columns[element__first_name][weight]' => '8',
      'columns[element__last_name][weight]' => '7',
      'sort' => 'element__first_name',
      'direction' => 'desc',
      'limit' => 20,
      'link_type' => 'table',
    ];
    $this->drupalPostForm('/admin/structure/webform/manage/test_submissions/results/submissions/custom', $edit, t('Save'));
    $this->assertRaw('The customized table has been saved.');

    // Check webform state.
    $actual_state = \Drupal::state()->get('webform.webform.test_submissions');
    $expected_state = [
      'results.custom.columns' => [
        0 => 'serial',
        1 => 'sid',
        2 => 'label',
        3 => 'uuid',
        4 => 'in_draft',
        5 => 'sticky',
        6 => 'locked',
        7 => 'notes',
        8 => 'element__last_name',
        9 => 'element__first_name',
        10 => 'completed',
        11 => 'changed',
        12 => 'entity',
        13 => 'uid',
        14 => 'remote_addr',
        15 => 'element__sex',
        16 => 'element__dob',
        17 => 'element__node',
        18 => 'element__colors',
        19 => 'element__likert',
        20 => 'element__likert__q1',
        21 => 'element__likert__q2',
        22 => 'element__likert__q3',
        23 => 'element__address',
        24 => 'element__address__address',
        25 => 'element__address__address_2',
        26 => 'element__address__city',
        27 => 'element__address__state_province',
        28 => 'element__address__postal_code',
        29 => 'element__address__country',
        30 => 'operations',
      ],
      'results.custom.sort' => 'element__first_name',
      'results.custom.direction' => 'desc',
      'results.custom.limit' => 20,
      'results.custom.link_type' => 'table',
      'results.custom.format' => [
        'header_format' => 'label',
        'element_format' => 'value',
      ],
      'results.custom.default' => TRUE,
    ];
    $this->assertEquals($expected_state, $actual_state);

    // Check that table now link to table.
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions');
    $this->assertRaw('data-webform-href="' . $submissions[0]->toUrl('table')->toString() . '"');
    $this->assertRaw('data-webform-href="' . $submissions[1]->toUrl('table')->toString() . '"');
    $this->assertRaw('data-webform-href="' . $submissions[2]->toUrl('table')->toString() . '"');

    // Check that sid is hidden and changed is visible.
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions');
    $this->assertNoRaw('sort by Created');
    $this->assertRaw('sort by Changed');

    // Check that first name is now after last name.
    $this->assertPattern('#Last name.+First name#ms');

    // Check the table results order by first name.
    $this->assertPattern('#Hillary.+George.+Abraham#ms');

    // Manually set the limit to 1.
    $webform->setState('results.custom.limit', 1);

    // Check that only one result (Hillary #2) is displayed with pager.
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions');
    $this->assertNoRaw('George');
    $this->assertNoRaw('Abraham');
    $this->assertNoRaw('Hillary');
    $this->assertRaw('quotes&#039; &quot;');
    $this->assertRaw('<nav class="pager" role="navigation" aria-labelledby="pagination-heading">');

    // Reset the limit to 20.
    $webform->setState('results.custom.limit', 20);

    // Check Header label and element value display.
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions');

    // Check user header and value.
    $this->assertTableHeaderSort('User');
    $this->assertRaw('<td class="priority-medium">Anonymous</td>');

    // Check date of birth.
    $this->assertTableHeaderSort('Date of birth');
    $this->assertRaw('<td>Sunday, October 26, 1947</td>');

    // Display Header key and element raw.
    $webform->setState('results.custom.format', [
      'header_format' => 'key',
      'element_format' => 'raw',
    ]);

    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions');

    // Check user header and value.
    $this->assertTableHeaderSort('uid');
    $this->assertRaw('<td class="priority-medium">0</td>');

    // Check date of birth.
    $this->assertTableHeaderSort('dob');
    $this->assertRaw('<td>1947-10-26</td>');

    /**************************************************************************/
    // Customize user results table.
    /**************************************************************************/

    // Switch to admin user.
    $this->drupalLogin($admin_user);

    // Clear customized default able.
    $this->drupalPostForm('/admin/structure/webform/manage/test_submissions/results/submissions/custom', $edit, t('Reset'));
    $this->assertRaw('The customized table has been reset.');

    // Check that 'Customize' button and link are visible.
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions');
    $this->assertRaw('>Customize<');
    $this->assertLinkByHref("${base_path}admin/structure/webform/manage/test_submissions/results/submissions/custom");

    // Enabled customized results.
    $webform->setSetting('results_customize', TRUE)->save();

    // Check that 'Customize' button and link are not visible.
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions');
    $this->assertNoRaw('>Customize<');
    $this->assertLinkByHref("${base_path}admin/structure/webform/manage/test_submissions/results/submissions/custom");

    // Check that 'Customize my table' button and link are visible.
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions');
    $this->assertRaw('>Customize my table<');
    $this->assertLinkByHref("${base_path}admin/structure/webform/manage/test_submissions/results/submissions/custom/user");

    // Check that first name is before last name.
    $this->assertPattern('#First name.+Last name#s');

    // Check that 'Customize default table' button and link are visible.
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions/custom/user');
    $this->assertRaw('>Customize default table<');
    $this->assertLinkByHref("${base_path}admin/structure/webform/manage/test_submissions/results/submissions/custom");

    // Switch to admin submission user.
    $this->drupalLogin($admin_submission_user);

    // Check that admin submission user is denied access to default table.
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions/custom');
    $this->assertResponse(403);

    // Check that admin submission user is allowed access to user table.
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions/custom/user');
    $this->assertResponse(200);

    // Customize to results user table.
    $edit = [
      'columns[element__first_name][weight]' => '8',
      'columns[element__last_name][weight]' => '7',
    ];
    $this->drupalPostForm('/admin/structure/webform/manage/test_submissions/results/submissions/custom/user', $edit, t('Save'));
    $this->assertRaw('Your customized table has been saved.');

    // Check that first name is now after last name.
    $this->assertPattern('#Last name.+First name#ms');

    // Switch to admin user.
    $this->drupalLogin($admin_user);

    // Customize to results default table.
    $edit = [
      'columns[element__first_name][checkbox]' => FALSE,
      'columns[element__last_name][checkbox]' => FALSE,
    ];
    $this->drupalPostForm('/admin/structure/webform/manage/test_submissions/results/submissions/custom', $edit, t('Save'));
    $this->assertRaw('The default customized table has been saved.');
    // Check that first name and last name are not visible.
    $this->assertNoRaw('First name');
    $this->assertNoRaw('Last name');

    // Switch to admin submission user.
    $this->drupalLogin($admin_submission_user);

    // Check that first name is still after last name.
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions');
    $this->assertPattern('#Last name.+First name#ms');

    // Check that disabled customized results don't pull user data.
    $webform->setSetting('results_customize', FALSE)->save();
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions');
    $this->assertNoRaw('First name');
    $this->assertNoRaw('Last name');

    // Check that first name is still after last name.
    $webform->setSetting('results_customize', TRUE)->save();
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions');
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions');
    $this->assertPattern('#Last name.+First name#ms');

    // Reset user customized table.
    $this->drupalPostForm('/admin/structure/webform/manage/test_submissions/results/submissions/custom/user', $edit, t('Reset'));
    $this->assertRaw('Your customized table has been reset.');

    // Check that first name and last name are now not visible.
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions');
    $this->assertNoRaw('First name');
    $this->assertNoRaw('Last name');

    /**************************************************************************/
    // Customize user results.
    /**************************************************************************/

    $this->drupalLogin($own_submission_user);

    // Check view own submissions.
    $this->drupalget('/webform/test_submissions/submissions');
    $this->assertRaw('<th specifier="serial">');
    $this->assertRaw('<th specifier="created" class="priority-medium is-active" aria-sort="descending">');
    $this->assertRaw('<th specifier="remote_addr" class="priority-low">');

    // Display only first name and last name columns.
    $webform->setSetting('submission_user_columns', ['element__first_name', 'element__last_name'])
      ->save();

    // Check view own submissions only include first name and last name.
    $this->drupalget('/webform/test_submissions/submissions');
    $this->assertNoRaw('<th specifier="serial">');
    $this->assertNoRaw('<th specifier="created" class="priority-medium is-active" aria-sort="descending">');
    $this->assertNoRaw('<th specifier="remote_addr" class="priority-low">');
    $this->assertRaw('<th specifier="element__first_name" aria-sort="ascending" class="is-active">');
    $this->assertRaw('<th specifier="element__last_name">');

    /**************************************************************************/
    // Webform delete.
    /**************************************************************************/

    // Switch to admin user.
    $this->drupalLogin($admin_user);

    // Set state and user data for the admin user.
    $edit = [
      'columns[element__first_name][weight]' => '8',
      'columns[element__last_name][weight]' => '7',
    ];
    $this->drupalPostForm('/admin/structure/webform/manage/test_submissions/results/submissions/custom', $edit, t('Save'));
    $edit = [
      'columns[element__first_name][weight]' => '8',
      'columns[element__last_name][weight]' => '7',
    ];
    $this->drupalPostForm('/admin/structure/webform/manage/test_submissions/results/submissions/custom/user', $edit, t('Save'));

    // Check that state and user data exists.
    $this->assertNotEmpty(\Drupal::state()->get('webform.webform.test_submissions'));
    $this->assertNotEmpty($user_data->get('webform', NULL, 'test_submissions'));

    // Delete the webform.
    $webform->delete();

    // Check that state and user data does not exist.
    $this->assertEmpty(\Drupal::state()->get('webform.webform.test_submissions'));
    $this->assertEmpty($user_data->get('webform', NULL, 'test_submissions'));
  }

  /**
   * Assert table header sorting.
   *
   * @param string $order
   *   Column table is sorted by.
   * @param string $sort
   *   Sort order for table column.
   * @param string|null $label
   *   Column label.
   */
  protected function assertTableHeaderSort($order, $sort = 'asc', $label = NULL) {
    global $base_path;

    $label = $label ?: $order;

    // @todo Remove once Drupal 8.9.x is only supported.
    if (floatval(\Drupal::VERSION) >= 8.9) {
      $this->assertRaw('<a href="' . $base_path . 'admin/structure/webform/manage/test_submissions/results/submissions?sort=' . $sort . '&amp;order=' . str_replace(' ', '%20', $order) . '" title="sort by ' . $label . '" rel="nofollow">' . $label . '</a>');
    }
    else {
      $this->assertRaw('<a href="' . $base_path . 'admin/structure/webform/manage/test_submissions/results/submissions?sort=' . $sort . '&amp;order=' . str_replace(' ', '%20', $order) . '" title="sort by ' . $label . '">' . $label . '</a>');
    }
  }

}
