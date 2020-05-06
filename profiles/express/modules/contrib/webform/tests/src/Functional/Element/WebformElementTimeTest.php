<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for webform time element.
 *
 * @group Webform
 */
class WebformElementTimeTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_time'];

  /**
   * Test time element.
   */
  public function testTime() {
    $this->drupalGet('/webform/test_element_time');

    // Check time element.
    $this->assertRaw('<label for="edit-time-12-hour">time_12_hour</label>');
    $this->assertRaw('<input data-drupal-selector="edit-time-12-hour" data-webform-time-format="g:i A" type="time" id="edit-time-12-hour" name="time_12_hour" value="14:00" size="12" maxlength="12" class="form-time webform-time" />');

    // Check timepicker elements.
    $this->assertRaw('<input data-drupal-selector="edit-time-timepicker" data-webform-time-format="g:i A" type="text" id="edit-time-timepicker" name="time_timepicker" value="2:00 PM" size="12" maxlength="12" class="form-time webform-time" />');
    $this->assertRaw('<input data-drupal-selector="edit-time-timepicker-min-max" aria-describedby="edit-time-timepicker-min-max--description" data-webform-time-format="g:i A" type="text" id="edit-time-timepicker-min-max" name="time_timepicker_min_max" value="2:00 PM" size="12" maxlength="12" min="14:00" max="18:00" class="form-time webform-time" />');
    $this->assertRaw('<input placeholder="{time}" data-drupal-selector="edit-time-timepicker-placeholder" data-webform-time-format="H:i" type="text" id="edit-time-timepicker-placeholder" name="time_timepicker_placeholder" value="" size="12" maxlength="12" class="form-time webform-time" />');

    // Check time processing.
    $this->drupalPostForm('/webform/test_element_time', [], t('Submit'));
    $time_12_hour_plus_6_hours = date('H:i:00', strtotime('+6 hours'));
    $this->assertRaw("time_default: '14:00:00'
time_24_hour: '14:00:00'
time_12_hour: '14:00:00'
time_12_hour_plus_6_hours: '$time_12_hour_plus_6_hours'
time_steps: '14:00:00'
time_min_max: '14:00:00'
time_timepicker: '14:00:00'
time_timepicker_min_max: '14:00:00'");

    // Check time validation.
    $edit = ['time_24_hour' => 'not-valid'];
    $this->drupalPostForm('/webform/test_element_time', $edit, t('Submit'));
    $this->assertRaw('<em class="placeholder">time_24_hour</em> must be a valid time.');

    // Check '0' string trigger validation error.
    $edit = ['time_default' => '0'];
    $this->drupalPostForm('/webform/test_element_time', $edit, t('Submit'));
    $this->assertRaw('em class="placeholder">time_default</em> must be a valid time.');

    // Check '++' string (faulty relative date) trigger validation error.
    $edit = ['time_default' => '++'];
    $this->drupalPostForm('/webform/test_element_time', $edit, t('Submit'));
    $this->assertRaw('em class="placeholder">time_default</em> must be a valid time.');

    // Check empty string trigger does not validation error.
    $edit = ['time_default' => ''];
    $this->drupalPostForm('/webform/test_element_time', $edit, t('Submit'));
    $this->assertNoRaw('<em class="placeholder">time_default</em> must be a valid time.');
    $this->assertRaw("time_default: ''");

    // Check time #max validation.
    $edit = ['time_min_max' => '12:00'];
    $this->drupalPostForm('/webform/test_element_time', $edit, t('Submit'));
    $this->assertRaw('<em class="placeholder">time_min_max</em> must be on or after <em class="placeholder">14:00</em>.');

    // Check time #min validation.
    $edit = ['time_min_max' => '22:00'];
    $this->drupalPostForm('/webform/test_element_time', $edit, t('Submit'));
    $this->assertRaw('<em class="placeholder">time_min_max</em> must be on or before <em class="placeholder">18:00</em>.');
  }

}
