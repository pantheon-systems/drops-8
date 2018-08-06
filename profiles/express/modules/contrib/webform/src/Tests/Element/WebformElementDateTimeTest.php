<?php

namespace Drupal\webform\Tests\Element;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\webform\Tests\WebformTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform datetime element.
 *
 * @group Webform
 */
class WebformElementDateTimeTest extends WebformTestBase {

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
    $this->drupalGet('webform/test_element_datetime');

    // Check '#format' values.
    $this->assertFieldByName('datetime_default[date]', '2009-08-18');
    $this->assertFieldByName('datetime_default[time]', '16:00:00');

    // Check datepicker and timepicker.
    $now_date = date('D, m/d/Y', strtotime('now'));
    $this->assertRaw('<input data-drupal-selector="edit-datetime-datepicker-timepicker-date" title="Date (e.g. ' . $now_date . ')" type="text" min="Mon, 01/01/1900" max="Sat, 12/31/2050" data-drupal-date-format="D, m/d/Y" id="edit-datetime-datepicker-timepicker-date" name="datetime_datepicker_timepicker[date]" value="Tue, 08/18/2009" size="15" maxlength="128" class="form-text" />');
    $this->assertRaw('<input data-drupal-selector="edit-datetime-datepicker-timepicker-time"');
    // Skip time which can change during the tests
    $this->assertRaw('type="text" step="1" data-webform-time-format="g:i A" id="edit-datetime-datepicker-timepicker-time" name="datetime_datepicker_timepicker[time]" value="4:00 PM" size="12" maxlength="128" class="form-text" />');

    // Check time with custom min/max/step attributes.
    $this->assertRaw('<input min="2009-01-01" data-min-year="2009" max="2009-12-31" data-max-year="2009" data-drupal-selector="edit-datetime-time-min-max-date"');
    $this->assertRaw('<input min="09:00:00" data-min-year="2009" max="17:00:00" data-max-year="2009" data-drupal-selector="edit-datetime-time-min-max-time"');
    $this->assertRaw('<input min="Thu, 01/01/2009" data-min-year="2009" max="Thu, 12/31/2009" data-max-year="2009" data-drupal-selector="edit-datetime-datepicker-timepicker-time-min-max-date"');
    $this->assertRaw('<input min="09:00:00" data-min-year="2009" max="17:00:00" data-max-year="2009" data-drupal-selector="edit-datetime-datepicker-timepicker-time-min-max-time"');

    // Check 'datelist' and 'datetime' #default_value.
    $form = $webform->getSubmissionForm();
    $this->assert($form['elements']['datetime_default']['#default_value'] instanceof DrupalDateTime, 'datetime_default #default_value instance of \Drupal\Core\Datetime\DrupalDateTime.');

    // Check datetime #max validation.
    $edit = ['datetime_min_max[date]' => '2010-08-18'];
    $this->drupalPostForm('webform/test_element_datetime', $edit, t('Submit'));
    $this->assertRaw('<em class="placeholder">datetime_min_max</em> must be on or before <em class="placeholder">2009-12-31</em>.');

    // Check datetime #min validation.
    $edit = ['datetime_min_max[date]' => '2006-08-18'];
    $this->drupalPostForm('webform/test_element_datetime', $edit, t('Submit'));
    $this->assertRaw('<em class="placeholder">datetime_min_max</em> must be on or after <em class="placeholder">2009-01-01</em>.');

    // Check: Issue #2723159: Datetime form element cannot validate when using a
    // format without seconds.
    $this->drupalPostForm('webform/test_element_datetime', [], t('Submit'));
    $this->assertNoRaw('The datetime_no_seconds date is invalid.');
  }

}
