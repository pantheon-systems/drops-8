<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform datetime element.
 *
 * @group Webform
 */
class WebformElementDateTimeTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_datetime'];

  /**
   * Test datetime element.
   */
  public function testDateTime() {
    $webform = Webform::load('test_element_datetime');

    // Check posted submission values.
    $this->postSubmission($webform);
    $this->assertRaw("datetime_default: '2009-08-18T16:00:00+1000'");
    $this->assertRaw("datetime_multiple:
  - '2009-08-18T16:00:00+1000'");
    $this->assertRaw("datetime_custom_composite:
  - datetime: '2009-08-18T16:00:00+1000'");

    $this->drupalGet('/webform/test_element_datetime');

    // Check datetime label has not for attributes.
    $this->assertRaw('<label>datetime_default</label>');

    // Check '#format' values.
    $this->assertFieldByName('datetime_default[date]', '2009-08-18');
    $this->assertFieldByName('datetime_default[time]', '16:00:00');

    // Check datepicker and timepicker.
    $now_date = date('D, m/d/Y', strtotime('now'));
    $this->assertRaw('<input data-drupal-selector="edit-datetime-datepicker-timepicker-date" title="Date (e.g. ' . $now_date . ')" type="text" min="Mon, 01/01/1900" max="Sat, 12/31/2050" data-drupal-date-format="D, m/d/Y" id="edit-datetime-datepicker-timepicker-date" name="datetime_datepicker_timepicker[date]" value="Tue, 08/18/2009" size="15" maxlength="128" class="form-text" />');
    $this->assertRaw('<input data-drupal-selector="edit-datetime-datepicker-timepicker-time"');
    // Skip time which can change during the tests.
    $this->assertRaw('type="text" step="1" data-webform-time-format="g:i A" id="edit-datetime-datepicker-timepicker-time" name="datetime_datepicker_timepicker[time]" value="4:00 PM" size="12" maxlength="12" class="form-time webform-time" />');

    // Check date/time placeholder attribute.
    $this->assertRaw(' type="text" data-drupal-date-format="Y-m-d" placeholder="{date}"');
    $this->assertRaw(' type="text" step="1" data-webform-time-format="H:i:s" placeholder="{time}"');

    // Check time with custom min/max/step attributes.
    $this->assertRaw('<input min="2009-01-01" data-min-year="2009" max="2009-12-31" data-max-year="2009" data-drupal-selector="edit-datetime-time-min-max-date"');
    $this->assertRaw('<input min="09:00:00" max="17:00:00" data-drupal-selector="edit-datetime-time-min-max-time"');
    $this->assertRaw('<input min="Thu, 01/01/2009" data-min-year="2009" max="Thu, 12/31/2009" data-max-year="2009" data-drupal-selector="edit-datetime-datepicker-timepicker-time-min-max-date"');
    $this->assertRaw('<input min="09:00:00" max="17:00:00" data-drupal-selector="edit-datetime-datepicker-timepicker-time-min-max-time"');

    // Check 'datelist' and 'datetime' #default_value.
    $form = $webform->getSubmissionForm();
    $this->assert($form['elements']['datetime_default']['#default_value'] instanceof DrupalDateTime, 'datetime_default #default_value instance of \Drupal\Core\Datetime\DrupalDateTime.');

    // Check datetime #date_date_max validation.
    $edit = ['datetime_min_max[date]' => '2010-08-18'];
    $this->drupalPostForm('/webform/test_element_datetime', $edit, t('Submit'));
    $this->assertRaw('<em class="placeholder">datetime_min_max</em> must be on or before <em class="placeholder">2009-12-31</em>.');

    // Check datetime #date_date_min validation.
    $edit = ['datetime_min_max[date]' => '2006-08-18'];
    $this->drupalPostForm('/webform/test_element_datetime', $edit, t('Submit'));
    $this->assertRaw('<em class="placeholder">datetime_min_max</em> must be on or after <em class="placeholder">2009-01-01</em>.');

    // Check datetime #date_max date validation.
    $edit = ['datetime_min_max_time[date]' => '2009-12-31', 'datetime_min_max_time[time]' => '19:00:00'];
    $this->drupalPostForm('/webform/test_element_datetime', $edit, t('Submit'));
    $this->assertRaw('<em class="placeholder">datetime_min_max_time</em> must be on or before <em class="placeholder">2009-12-31 17:00:00</em>.');

    // Check datetime #date_min date validation.
    $edit = ['datetime_min_max_time[date]' => '2009-01-01', 'datetime_min_max_time[time]' => '08:00:00'];
    $this->drupalPostForm('/webform/test_element_datetime', $edit, t('Submit'));
    $this->assertRaw('<em class="placeholder">datetime_min_max_time</em> must be on or after <em class="placeholder">2009-01-01 09:00:00</em>.');

    // Check datetime #date_max time validation.
    $edit = ['datetime_min_max_time[time]' => '01:00:00'];
    $this->drupalPostForm('/webform/test_element_datetime', $edit, t('Submit'));
    $this->assertRaw('<em class="placeholder">datetime_min_max_time: Time</em> must be on or after <em class="placeholder">09:00:00</em>.');
    $this->assertRaw('<em class="placeholder">datetime_min_max_time</em> must be on or after <em class="placeholder">2009-01-01 09:00:00</em>.');

    // Check datetime #date_min time validation.
    $edit = ['datetime_min_max_time[time]' => '01:00:00'];
    $this->drupalPostForm('/webform/test_element_datetime', $edit, t('Submit'));
    $this->assertRaw('<em class="placeholder">datetime_min_max_time: Time</em> must be on or after <em class="placeholder">09:00:00</em>.');
    $this->assertRaw('<em class="placeholder">datetime_min_max_time</em> must be on or after <em class="placeholder">2009-01-01 09:00:00</em>.');

    // Check: Issue #2723159: Datetime form element cannot validate when using a
    // format without seconds.
    $sid = $this->postSubmission($webform);
    $submission = WebformSubmission::load($sid);
    $this->assertNoRaw('The datetime_no_seconds date is invalid.');
    $this->assertEqual($submission->getElementData('datetime_no_seconds'), '2009-08-18T16:00:00+1000');
  }

}
