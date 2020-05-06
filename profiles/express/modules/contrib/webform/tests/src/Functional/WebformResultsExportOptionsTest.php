<?php

namespace Drupal\Tests\webform\Functional;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform results export.
 *
 * @group Webform
 */
class WebformResultsExportOptionsTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'locale', 'webform', 'webform_test_submissions'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_submissions'];

  /**
   * Tests export options.
   */
  public function testExportOptions() {
    $admin_submission_user = $this->drupalCreateUser([
      'administer webform submission',
    ]);

    /**************************************************************************/

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_submissions');
    /** @var \Drupal\webform\WebformSubmissionInterface[] $submissions */
    $submissions = array_values(\Drupal::entityTypeManager()->getStorage('webform_submission')->loadByProperties(['webform_id' => 'test_submissions']));
    /** @var \Drupal\node\NodeInterface[] $node */
    $nodes = array_values(\Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'webform_test_submissions']));

    $this->drupalLogin($admin_submission_user);

    // Check default options.
    $this->getExport($webform);
    $this->assertRaw('"First name","Last name"');
    $this->assertRaw('George,Washington');
    $this->assertRaw('Abraham,Lincoln');
    $this->assertRaw('Hillary,Clinton');

    // Check special characters.
    $this->assertRaw("quotes' \"\"", "html <markup>");

    // Check delimiter.
    $this->getExport($webform, ['delimiter' => '|']);
    $this->assertRaw('"First name"|"Last name"');
    $this->assertRaw('George|Washington');

    // Check header keys = label.
    $this->getExport($webform, ['header_format' => 'label']);
    $this->assertRaw('"First name","Last name"');

    // Check header keys = key.
    $this->getExport($webform, ['header_format' => 'key']);
    $this->assertRaw('first_name,last_name');

    // Check options format compact.
    $this->getExport($webform, ['options_single_format' => 'compact', 'options_multiple_format' => 'compact']);
    $this->assertRaw('"Flag colors"');
    $this->assertRaw('Red;White;Blue');

    // Check options format separate.
    $this->getExport($webform, ['options_single_format' => 'separate', 'options_multiple_format' => 'separate']);
    $this->assertRaw('"Flag colors: Red","Flag colors: White","Flag colors: Blue"');
    $this->assertNoRaw('"Flag colors"');
    $this->assertRaw('X,X,X');
    $this->assertNoRaw('Red;White;Blue');

    // Check options item format label.
    $this->getExport($webform, ['options_item_format' => 'label']);
    $this->assertRaw('Red;White;Blue');

    // Check options item format key.
    $this->getExport($webform, ['options_item_format' => 'key']);
    $this->assertNoRaw('Red;White;Blue');
    $this->assertRaw('red;white;blue');

    // Check multiple delimiter.
    $this->getExport($webform, ['multiple_delimiter' => '|']);
    $this->assertRaw('Red|White|Blue');
    $this->getExport($webform, ['multiple_delimiter' => ',']);
    $this->assertRaw('"Red,White,Blue"');

    // Check entity reference format link.
    $this->getExport($webform, ['entity_reference_items' => 'id,title,url']);
    $this->assertRaw('"Favorite node: ID","Favorite node: Title","Favorite node: URL"');
    $this->assertRaw('' . $nodes[0]->id() . ',"' . $nodes[0]->label() . '",' . $nodes[0]->toUrl('canonical', ['absolute' => TRUE])->toString());

    // Check entity reference format title and url.
    $this->getExport($webform, ['entity_reference_items' => 'id']);
    $this->getExport($webform, ['entity_reference_items' => 'title,url']);
    $this->assertNoRaw('"Favorite node: ID","Favorite node: Title","Favorite node: URL"');
    $this->assertNoRaw('' . $nodes[0]->id() . ',"' . $nodes[0]->label() . '",' . $nodes[0]->toUrl('canonical', ['absolute' => TRUE])->toString());
    $this->assertRaw('"Favorite node: Title","Favorite node: URL"');
    $this->assertRaw('"' . $nodes[0]->label() . '",' . $nodes[0]->toUrl('canonical', ['absolute' => TRUE])->toString());

    // Check likert questions format label.
    $this->getExport($webform, ['header_format' => 'label']);
    $this->assertRaw('"Likert: Question 1","Likert: Question 2","Likert: Question 3"');

    // Check likert questions format key.
    $this->getExport($webform, ['header_format' => 'key']);
    $this->assertNoRaw('"Likert: Question 1","Likert: Question 2","Likert: Question 3"');
    $this->assertRaw('likert__q1,likert__q2,likert__q3');

    // Check likert answers format label.
    $this->getExport($webform, ['likert_answers_format' => 'label']);
    $this->assertRaw('"Answer 1","Answer 1","Answer 1"');

    // Check likert answers format key.
    $this->getExport($webform, ['likert_answers_format' => 'key']);
    $this->assertNoRaw('"Option 1","Option 1","Option 1"');
    $this->assertRaw('1,1,1');

    // Check composite w/o header prefix.
    $this->getExport($webform, ['header_format' => 'label', 'header_prefix' => TRUE]);
    $this->assertRaw('"Address: Address","Address: Address 2","Address: City/Town","Address: State/Province","Address: ZIP/Postal Code","Address: Country"');

    // Check composite w header prefix.
    $this->getExport($webform, ['header_format' => 'label', 'header_prefix' => FALSE]);
    $this->assertRaw('Address,"Address 2",City/Town,State/Province,"ZIP/Postal Code",Country');

    // Check limit.
    $this->getExport($webform, ['range_type' => 'latest', 'range_latest' => 2]);
    $this->assertRaw('Hillary,Clinton');
    $this->assertNoRaw('George,Washington');
    $this->assertNoRaw('Abraham,Lincoln');

    // Check sort ASC.
    $this->getExport($webform, ['order' => 'asc']);
    $this->assertPattern('/George.*Abraham.*Hillary/ms');

    // Check sort DESC.
    $this->getExport($webform, ['order' => 'desc']);
    $this->assertPattern('/Hillary.*Abraham.*George/ms');

    // Check sid start.
    $this->getExport($webform, ['range_type' => 'sid', 'range_start' => $submissions[1]->id()]);
    $this->assertNoRaw('George,Washington');
    $this->assertRaw('Abraham,Lincoln');
    $this->assertRaw('Hillary,Clinton');

    // Check sid range.
    $this->getExport($webform, ['range_type' => 'sid', 'range_start' => $submissions[1]->id(), 'range_end' => $submissions[1]->id()]);
    $this->assertNoRaw('George,Washington');
    $this->assertRaw('Abraham,Lincoln');
    $this->assertNoRaw('Hillary,Clinton');

    // Check date range.
    $this->getExport($webform, ['range_type' => 'date', 'range_start' => '2000-01-01', 'range_end' => '2001-01-01']);
    $this->assertRaw('George,Washington');
    $this->assertRaw('Abraham,Lincoln');
    $this->assertNoRaw('Hillary,Clinton');

    // Check entity type and id hidden.
    $this->drupalGet('/admin/structure/webform/manage/' . $webform->id() . '/results/download');
    $this->assertNoFieldById('edit-entity-type', NULL);

    // Change submission 0 & 1 to be submitted user account.
    $submissions[0]->set('entity_type', 'user')->set('entity_id', '1')->save();
    $submissions[1]->set('entity_type', 'user')->set('entity_id', '2')->save();

    // Check entity type and id visible.
    $this->drupalGet('/admin/structure/webform/manage/' . $webform->id() . '/results/download');
    $this->assertFieldById('edit-entity-type', NULL);

    // Check entity type limit.
    $this->getExport($webform, ['entity_type' => 'user']);
    $this->assertRaw('George,Washington');
    $this->assertRaw('Abraham,Lincoln');
    $this->assertNoRaw('Hillary,Clinton');

    // Check entity type and id limit.
    $this->getExport($webform, ['entity_type' => 'user', 'entity_id' => '1']);
    $this->assertRaw('George,Washington');
    $this->assertNoRaw('Abraham,Lincoln');
    $this->assertNoRaw('Hillary,Clinton');

    $this->drupalLogin($this->rootUser);

    // Check changing default exporter to 'table' settings.
    $edit = [
      'exporter' => 'table',
    ];
    $this->drupalPostForm('/admin/structure/webform/manage/' . $webform->id() . '/results/download', $edit, t('Download'));
    $this->assertRaw('<body><table border="1"><thead><tr bgcolor="#cccccc" valign="top"><th>Serial number</th>');
    $this->assertPattern('#<td>George</td>\s+<td>Washington</td>\s+<td>Male</td>#ms');

    // Check changing default export (delimiter) settings.
    $edit = [
      'exporter' => 'delimited',
      'exporters[delimited][delimiter]' => '|',
    ];
    $this->drupalPostForm('/admin/structure/webform/config/exporters', $edit, t('Save configuration'));
    $this->drupalPostForm('/admin/structure/webform/manage/' . $webform->id() . '/results/download', [], t('Download'));
    $this->assertRaw('"Submission ID"|"Submission URI"');

    // Check saved webform export (delimiter) settings.
    $edit = [
      'exporter' => 'delimited',
      'exporters[delimited][delimiter]' => '.',
    ];
    $this->drupalPostForm('/admin/structure/webform/manage/' . $webform->id() . '/results/download', $edit, t('Save settings'));
    $this->drupalPostForm('/admin/structure/webform/manage/' . $webform->id() . '/results/download', [], t('Download'));
    $this->assertRaw('"Submission ID"."Submission URI"');

    // Check delete webform export (delimiter) settings.
    $this->drupalPostForm('/admin/structure/webform/manage/' . $webform->id() . '/results/download', [], t('Reset settings'));
    $this->drupalPostForm('/admin/structure/webform/manage/' . $webform->id() . '/results/download', [], t('Download'));
    $this->assertRaw('"Submission ID"|"Submission URI"');
  }

}
